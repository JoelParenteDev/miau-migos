<?php
// ============================================================
// SEÇÃO DE PROCESSAMENTO DA REQUISIÇÃO (executada primeiro)
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui dependências
require_once __DIR__ . '../database/conexao.php';
require_once __DIR__ . '../dao/arquivo-modelo.php';
require_once __DIR__ . '../models/arquivo-modelo.php';

// Obtém ação da URL
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Resposta padrão
$response = ['sucesso' => false, 'erro' => 'Ação inválida'];

// Se a ação for cadastrar ou excluir, processa aqui antes de qualquer saída
if (($action === 'cadastrar' && $_SERVER['REQUEST_METHOD'] === 'POST') ||
    ($action === 'excluir' && $_SERVER['REQUEST_METHOD'] === 'POST')) {
    
    try {
        $pdo = Conexao::conectar();
        $dao = new EleicaoDAO($pdo);

        if ($action === 'cadastrar') {
            // Coleta dados
            $titulo      = trim($_POST['titulo'] ?? '');
            $ano_letivo  = (int)($_POST['ano_letivo'] ?? 0);
            $tipo        = $_POST['tipo'] ?? 'gremio';
            $turma_id    = isset($_POST['turma_id']) ? (int)$_POST['turma_id'] : 0;
            $descricao   = trim($_POST['descricao'] ?? '');
            $observacoes = trim($_POST['observacoes'] ?? '');
            $data_inicio = $_POST['data_inicio'] ?? '';
            $data_fim    = $_POST['data_fim'] ?? '';

            // Validação
            $erros = [];
            if (empty($titulo)) $erros[] = 'Título é obrigatório';
            if ($ano_letivo < 2000) $erros[] = 'Ano letivo inválido';
            if (empty($data_inicio)) $erros[] = 'Data de início é obrigatória';
            if (empty($data_fim)) $erros[] = 'Data de término é obrigatória';
            // Eleição "de sala" (tudo que não for grêmio) exige turma vinculada
            if ($tipo !== 'gremio' && $turma_id <= 0) $erros[] = 'Selecione a turma desta eleição de sala';

            if (!empty($erros)) {
                $response = ['sucesso' => false, 'erro' => implode(', ', $erros)];
            } else {
                $eleicao = new Eleicao();
                $eleicao->setTitulo($titulo);
                $eleicao->setAnoLetivo($ano_letivo);
                $eleicao->setTipo($tipo);
                // Grêmio nunca tem turma; eleição de sala sempre tem
                $eleicao->setTurmaId($tipo === 'gremio' ? null : $turma_id);
                $eleicao->setDescricao($descricao);
                $eleicao->setObservacoes($observacoes);
                $eleicao->setDataInicio($data_inicio);
                $eleicao->setDataFim($data_fim);
                $eleicao->setStatus('aberta');
                $eleicao->setStatusEtapa('preparacao');
                $eleicao->setCriadoPor($_SESSION['usuario']['id'] ?? null);

                $id = $dao->insert($eleicao);
                if ($id) {
                    // (Opcional) Adiciona o presidente à comissão
                    $response = ['sucesso' => true, 'id' => $id];
                } else {
                    $response = ['sucesso' => false, 'erro' => 'Erro ao inserir no banco'];
                }
            }
        } elseif ($action === 'excluir') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id > 0) {
                $deleted = $dao->delete($id);
                if ($deleted) {
                    $response = ['sucesso' => true];
                } else {
                    $response = ['sucesso' => false, 'erro' => 'Erro ao excluir eleição'];
                }
            } else {
                $response = ['sucesso' => false, 'erro' => 'ID inválido'];
            }
        }
    } catch (Exception $e) {
        $response = ['sucesso' => false, 'erro' => 'Exceção: ' . $e->getMessage()];
    }

    // Envia resposta JSON e encerra
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode($response);
    exit;
}
class EleicaoController {
    private $dao;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->dao = new EleicaoDAO($pdo);
    }

    // Listar todas as eleições (para a página eleicoes.php)
    public function listar() {
        return $this->dao->findAll();
    }

    // Buscar uma eleição por ID (para detalhes)
    public function buscar($id) {
        return $this->dao->findById($id);
    }

    // Cadastrar nova eleição (processa o POST do form)
    public function cadastrar($dados) {
        // Validar campos obrigatórios
        $erros = [];
        if (empty($dados['titulo'])) $erros[] = 'Título é obrigatório';
        if (empty($dados['ano_letivo'])) $erros[] = 'Ano letivo é obrigatório';
        if (empty($dados['data_inicio'])) $erros[] = 'Data de início é obrigatória';
        if (empty($dados['data_fim'])) $erros[] = 'Data de término é obrigatória';

        $tipo = $dados['tipo'] ?? 'gremio';
        $turmaId = isset($dados['turma_id']) ? (int)$dados['turma_id'] : 0;
        if ($tipo !== 'gremio' && $turmaId <= 0) $erros[] = 'Selecione a turma desta eleição de sala';

        if (!empty($erros)) {
            return ['erro' => implode(', ', $erros)];
        }

        // Criar objeto Eleicao
        $eleicao = new Eleicao();
        $eleicao->setTitulo($dados['titulo']);
        $eleicao->setAnoLetivo((int)$dados['ano_letivo']);
        $eleicao->setTipo($tipo);
        $eleicao->setTurmaId($tipo === 'gremio' ? null : $turmaId);
        $eleicao->setDescricao($dados['descricao'] ?? '');
        $eleicao->setObservacoes($dados['observacoes'] ?? '');
        $eleicao->setDataInicio($dados['data_inicio']);
        $eleicao->setDataFim($dados['data_fim']);
        $eleicao->setStatus('aberta');
        $eleicao->setStatusEtapa('preparacao');
        $eleicao->setCriadoPor($_SESSION['usuario']['id'] ?? null);

        $id = $this->dao->insert($eleicao);
        if ($id) {
            return ['sucesso' => true, 'id' => $id];
        } else {
            return ['erro' => 'Erro ao cadastrar eleição'];
        }
    }

    // Atualizar eleição (para edição)
    public function atualizar($id, $dados) {
        $eleicao = $this->dao->findById($id);
        if (!$eleicao) {
            return ['erro' => 'Eleição não encontrada'];
        }

        if (!empty($dados['titulo'])) $eleicao->setTitulo($dados['titulo']);
        if (!empty($dados['ano_letivo'])) $eleicao->setAnoLetivo((int)$dados['ano_letivo']);
        if (!empty($dados['tipo'])) $eleicao->setTipo($dados['tipo']);
        if (array_key_exists('turma_id', $dados)) {
            $novoTipo = $dados['tipo'] ?? $eleicao->getTipo();
            $turmaId = (int)$dados['turma_id'];
            if ($novoTipo !== 'gremio' && $turmaId <= 0) {
                return ['erro' => 'Selecione a turma desta eleição de sala'];
            }
            $eleicao->setTurmaId($novoTipo === 'gremio' ? null : $turmaId);
        }
        if (isset($dados['descricao'])) $eleicao->setDescricao($dados['descricao']);
        if (isset($dados['observacoes'])) $eleicao->setObservacoes($dados['observacoes']);
        if (!empty($dados['data_inicio'])) $eleicao->setDataInicio($dados['data_inicio']);
        if (!empty($dados['data_fim'])) $eleicao->setDataFim($dados['data_fim']);
        if (!empty($dados['status'])) $eleicao->setStatus($dados['status']);
        if (!empty($dados['status_etapa'])) $eleicao->setStatusEtapa($dados['status_etapa']);

        $rows = $this->dao->update($eleicao);
        if ($rows !== false) {
            return ['sucesso' => true];
        } else {
            return ['erro' => 'Erro ao atualizar eleição'];
        }
    }

    // Excluir eleição
    public function excluir($id) {
        return $this->dao->delete($id) > 0;
    }

    // Estatísticas para os cards
    public function getEstatisticas() {
        $totalEleicoes = $this->dao->countAll();
        $totalChapas = 0;
        $totalComissao = 0;
        $totalVotos = 0;

        // Para simplificar, calculamos somando de todas as eleições
        $eleicoes = $this->dao->findAll();
        foreach ($eleicoes as $e) {
            $totalChapas += $this->dao->countChapasByEleicao($e->getId());
            $totalComissao += $this->dao->countComissaoByEleicao($e->getId());
            $totalVotos += $this->dao->countVotosByEleicao($e->getId());
        }

        return [
            'total_eleicoes' => $totalEleicoes,
            'total_chapas' => $totalChapas,
            'total_comissao' => $totalComissao,
            'total_eleitores' => $totalVotos, // ou buscar total de alunos ativos
        ];
    }

    // Buscar eleição ativa para exibir no card "Etapa Atual"
    public function getEtapaAtual() {
        $ativa = $this->dao->findActive();
        if ($ativa) {
            return [
                'titulo' => $ativa->getTitulo(),
                'etapa' => $ativa->getEtapaLabel()
            ];
        }
        return null;
    }
}
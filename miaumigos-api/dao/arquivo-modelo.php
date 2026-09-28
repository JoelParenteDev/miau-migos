<?php
require_once __DIR__ . '../models/arquivo-modelo.php';

class EleicaoDAO {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Trecho de SELECT reaproveitado (traz o rótulo da turma quando houver)
    private function selectComTurma() {
        return "SELECT e.*, 
                       t.serie AS turma_serie, t.identificador AS turma_identificador, t.curso_nome AS turma_curso_nome
                FROM eleicoes e
                LEFT JOIN turmas t ON e.turma_id = t.id ";
    }

    // Buscar todas as eleições (eleições de sala em andamento primeiro)
    public function findAll() {
        $sql = $this->selectComTurma() . "
                ORDER BY 
                    CASE WHEN e.status = 'aberta' AND e.status_etapa <> 'finalizada' THEN 0 ELSE 1 END,
                    e.created_at DESC";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $eleicoes = [];
        foreach ($rows as $row) {
            $eleicoes[] = $this->hydrate($row);
        }
        return $eleicoes;
    }

    // Buscar por ID
    public function findById($id) {
        $sql = $this->selectComTurma() . " WHERE e.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $this->hydrate($row);
        }
        return null;
    }

    /**
     * Eleições visíveis para um aluno de determinada turma: eleições de
     * Grêmio (turma_id NULL, abertas a todos) + eleições de sala da própria
     * turma. Se $turmaId for null (admin/professor), retorna todas.
     */
    public function findVisiveisParaTurma($turmaId) {
        if ($turmaId === null) {
            return $this->findAll();
        }
        $sql = $this->selectComTurma() . "
                WHERE e.turma_id IS NULL OR e.turma_id = :turma_id
                ORDER BY 
                    CASE WHEN e.status = 'aberta' AND e.status_etapa <> 'finalizada' THEN 0 ELSE 1 END,
                    e.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':turma_id' => $turmaId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $eleicoes = [];
        foreach ($rows as $row) {
            $eleicoes[] = $this->hydrate($row);
        }
        return $eleicoes;
    }

    // Inserir nova eleição
    public function insert(Eleicao $eleicao) {
        $sql = "INSERT INTO eleicoes 
                (titulo, ano_letivo, tipo, turma_id, descricao, observacoes, data_inicio, data_fim, status, status_etapa, criado_por)
                VALUES 
                (:titulo, :ano_letivo, :tipo, :turma_id, :descricao, :observacoes, :data_inicio, :data_fim, :status, :status_etapa, :criado_por)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $eleicao->getTitulo(),
            ':ano_letivo' => $eleicao->getAnoLetivo(),
            ':tipo' => $eleicao->getTipo(),
            ':turma_id' => $eleicao->getTurmaId(),
            ':descricao' => $eleicao->getDescricao(),
            ':observacoes' => $eleicao->getObservacoes(),
            ':data_inicio' => $eleicao->getDataInicio(),
            ':data_fim' => $eleicao->getDataFim(),
            ':status' => $eleicao->getStatus(),
            ':status_etapa' => $eleicao->getStatusEtapa(),
            ':criado_por' => $eleicao->getCriadoPor()
        ]);
        return $this->pdo->lastInsertId();
    }

    // Atualizar
    public function update(Eleicao $eleicao) {
        $sql = "UPDATE eleicoes SET 
                    titulo = :titulo,
                    ano_letivo = :ano_letivo,
                    tipo = :tipo,
                    turma_id = :turma_id,
                    descricao = :descricao,
                    observacoes = :observacoes,
                    data_inicio = :data_inicio,
                    data_fim = :data_fim,
                    status = :status,
                    status_etapa = :status_etapa,
                    criado_por = :criado_por
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $eleicao->getTitulo(),
            ':ano_letivo' => $eleicao->getAnoLetivo(),
            ':tipo' => $eleicao->getTipo(),
            ':turma_id' => $eleicao->getTurmaId(),
            ':descricao' => $eleicao->getDescricao(),
            ':observacoes' => $eleicao->getObservacoes(),
            ':data_inicio' => $eleicao->getDataInicio(),
            ':data_fim' => $eleicao->getDataFim(),
            ':status' => $eleicao->getStatus(),
            ':status_etapa' => $eleicao->getStatusEtapa(),
            ':criado_por' => $eleicao->getCriadoPor(),
            ':id' => $eleicao->getId()
        ]);
        return $stmt->rowCount();
    }

    // Excluir
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM eleicoes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    // Método auxiliar para hidratar objeto
    private function hydrate($row) {
        $e = new Eleicao();
        $e->setId($row['id']);
        $e->setTitulo($row['titulo']);
        $e->setAnoLetivo($row['ano_letivo']);
        $e->setTipo($row['tipo']);
        $e->setTurmaId($row['turma_id'] ?? null);
        if (!empty($row['turma_identificador'])) {
            $serieLabel = !empty($row['turma_serie']) ? $row['turma_serie'] . 'ª ' : '';
            $curso = !empty($row['turma_curso_nome']) ? ' - ' . $row['turma_curso_nome'] : '';
            $e->setTurmaLabel(trim($serieLabel . $row['turma_identificador']) . $curso);
        }
        $e->setDescricao($row['descricao']);
        $e->setObservacoes($row['observacoes']);
        $e->setDataInicio($row['data_inicio']);
        $e->setDataFim($row['data_fim']);
        $e->setStatus($row['status']);
        $e->setStatusEtapa($row['status_etapa']);
        $e->setCriadoPor($row['criado_por']);
        $e->setCreatedAt($row['created_at']);
        // updated_at não existe na tabela, mas você pode adicionar se quiser
        return $e;
    }

    // Métodos adicionais para estatísticas
    public function countAll() {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM eleicoes")->fetchColumn();
    }

    public function countChapasByEleicao($eleicao_id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM chapas WHERE eleicao_id = :id");
        $stmt->execute([':id' => $eleicao_id]);
        return (int) $stmt->fetchColumn();
    }

    public function countComissaoByEleicao($eleicao_id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM comissao_eleitoral WHERE eleicao_id = :id");
        $stmt->execute([':id' => $eleicao_id]);
        return (int) $stmt->fetchColumn();
    }

    public function countVotosByEleicao($eleicao_id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM votos WHERE eleicao_id = :id");
        $stmt->execute([':id' => $eleicao_id]);
        return (int) $stmt->fetchColumn();
    }

    // Buscar eleição ativa (não finalizada)
    public function findActive() {
        $sql = $this->selectComTurma() . " WHERE e.status_etapa != 'finalizada' ORDER BY e.created_at DESC LIMIT 1";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $this->hydrate($row);
        }
        return null;
    }
}
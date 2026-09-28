<?php
class Eleicao
{
    private $id;
    private $titulo;
    private $ano_letivo;
    private $tipo;
    private $turma_id;
    private $descricao;
    private $observacoes;
    private $data_inicio;
    private $data_fim;
    private $status;
    private $status_etapa;
    private $criado_por;
    private $created_at;
    private $updated_at;

    // Preenchido via JOIN pelo DAO (não é coluna própria de `eleicoes`)
    private $turma_label;

    // Getters e Setters
    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTitulo()
    {
        return $this->titulo;
    }
    public function setTitulo($titulo)
    {
        $this->titulo = $titulo;
    }

    public function getAnoLetivo()
    {
        return $this->ano_letivo;
    }
    public function setAnoLetivo($ano)
    {
        $this->ano_letivo = $ano;
    }

    public function getTipo()
    {
        return $this->tipo;
    }
    public function setTipo($tipo)
    {
        $this->tipo = $tipo;
    }

    public function getTurmaId()
    {
        return $this->turma_id;
    }
    public function setTurmaId($turma_id)
    {
        $this->turma_id = $turma_id ?: null;
    }

    public function getTurmaLabel()
    {
        return $this->turma_label;
    }
    public function setTurmaLabel($label)
    {
        $this->turma_label = $label;
    }

    /** Eleição de Grêmio: aberta a todos os alunos, sem turma vinculada */
    public function ehGremio()
    {
        return $this->tipo === 'gremio';
    }

    /** Eleição "de sala" (representante/líder): restrita a uma turma */
    public function ehDeSala()
    {
        return $this->tipo !== 'gremio';
    }

    public function getDescricao()
    {
        return $this->descricao;
    }
    public function setDescricao($desc)
    {
        $this->descricao = $desc;
    }

    public function getObservacoes()
    {
        return $this->observacoes;
    }
    public function setObservacoes($obs)
    {
        $this->observacoes = $obs;
    }

    public function getDataInicio()
    {
        return $this->data_inicio;
    }
    public function setDataInicio($data)
    {
        $this->data_inicio = $data;
    }

    public function getDataFim()
    {
        return $this->data_fim;
    }
    public function setDataFim($data)
    {
        $this->data_fim = $data;
    }

    public function getStatus()
    {
        return $this->status;
    }
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatusEtapa()
    {
        return $this->status_etapa;
    }
    public function setStatusEtapa($etapa)
    {
        $this->status_etapa = $etapa;
    }

    public function getCriadoPor()
    {
        return $this->criado_por;
    }
    public function setCriadoPor($id)
    {
        $this->criado_por = $id;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }
    public function setCreatedAt($date)
    {
        $this->created_at = $date;
    }
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
    public function setUpdatedAt($date)
    {
        $this->updated_at = $date;
    }

    // Método para retornar o nome da etapa legível
    public function getEtapaLabel()
    {
        $map = [
            'preparacao' => 'Preparação',
            'inscricao_chapas' => 'Inscrição de Chapas',
            'campanha' => 'Campanha',
            'votacao' => 'Votação',
            'apuracao' => 'Apuração',
            'finalizada' => 'Finalizada'
        ];
        return isset($map[$this->status_etapa]) ? $map[$this->status_etapa] : $this->status_etapa;
    }

    // Método para retornar o status legível
    public function getStatusLabel()
    {
        $map = [
            'aberta' => 'Aberta',
            'encerrada' => 'Encerrada',
            'cancelada' => 'Cancelada'
        ];
        return isset($map[$this->status]) ? $map[$this->status] : $this->status;
    }
}
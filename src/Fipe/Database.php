<?php
/**
 * Fipe Database
 *
 * @category Database
 * @package  Fipe
 * @author   Rafael Goulart <rafaelgou@gmail.com>
 * @license  MIT <https://github.com/rafaelgou/fipe-crawler/LICENSE.md>
 * @link     https://github.com/rafaelgou/fipe-crawler
 */

namespace Fipe;

/**
 * Classe Database
 *
 * @category Database
 * @package  Fipe
 * @author   Rafael Goulart <rafaelgou@gmail.com>
 * @license  MIT <https://github.com/rafaelgou/fipe-crawler/LICENSE.md>
 * @link     https://github.com/rafaelgou/fipe-crawler
 */
class Database
{

    /**
     * The PDO connection
     *
     * @var PDO
     */
    protected $conn = null;

    /**
     * Meses
     *
     * @var array
     */
    public static $meses = array(
        'janeiro'   => '01',
        'fevereiro' => '02',
        'março'     => '03',
        'abril'     => '04',
        'maio'      => '05',
        'junho'     => '06',
        'julho'     => '07',
        'agosto'    => '08',
        'setembro'  => '09',
        'outubro'   => '10',
        'novembro'  => '11',
        'dezembro'  => '12',
    );

    /**
     * Combustíveis
     *
     * @var array
     */
    public static $combustiveis = array(
        1 => 'Gasolina',
        2 => 'Álcool',
        3 => 'Diesel',
        4 => 'Flex',
    );

    /**
     * Tipos de veículos
     *
     * @var array
     */
    public static $tipos = array(
        1 => 'Carro',
        2 => 'Moto',
        3 => 'Caminhão',
    );

    /**
     * Construtor
     *
     * @param string $host   Servidor
     * @param string $dbname Nome do banco
     * @param string $user   Usuário
     * @param string $pass   Senha
     *
     * @return void
     */
    public function __construct($host, $dbname, $user, $pass)
    {
        $dsn = "mysql:dbname={$dbname};host={$host}";
        try {
            $this->conn = new \PDO($dsn, $user, $pass);
        } catch (PDOException $e) {
            echo 'Connection failed: '.$e->getMessage();
        }
    }

    /**
     * Salva tabelas
     *
     * @param array $tabelas Tabelas
     *
     * @return array
     */
    public function saveTabelas(array $tabelas)
    {
        $results = array();

        $sql = "INSERT INTO tabela (id, desc, ano, mes) "."VALUES (:id, :desc, :ano, :mes);";
        $stmt = $this->conn->prepare(
            $sql,
            array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
        );
        foreach ($tabelas as $id => $desc) {
            $tmp = explode('/', $desc);
            $ano = $tmp[1];
            $mes = self::$meses[$tmp[0]];
            $mesesFlip = array_flip(self::$meses);

            $record = array(
                ':id'   => $id,
                ':desc' => "{$mesesFlip[$mes]}/{$ano}",
                ':ano'  => $ano,
                ':mes'  => $mes,
            );
            $stmt->execute($record);
            $results[] = $record;
        }

        return $results;
    }

    /**
     * Salva marcas
     *
     * @param array  $marcas Marcas
     * @param string $tipo   Tipo
     *
     * @return array
     */
    public function saveMarcas(array $marcas, $tipo)
    {
        $results = array();

        $sql = "INSERT INTO marca (id, desc, tipo) "."VALUES (:id, :desc, :tipo);";
        $stmt = $this->conn->prepare(
            $sql,
            array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
        );
        foreach ($marcas as $id => $desc) {
            $record = array(
                ':id'   => $id,
                ':desc' => $desc,
                ':tipo' => $tipo,
            );
            $stmt->execute($record);
            $record = $this->cleanRecord($record);
            $results[] = $record;
        }

        return $results;
    }

    /**
     * Salva modelos
     *
     * @param array   $modelos Modelos
     * @param integer $marcaId Id da Marca
     *
     * @return array
     */
    public function saveModelos(array $modelos, $marcaId)
    {
        $results = array();

        $sql = "INSERT INTO modelo (id, marca_id, desc) "."VALUES (:id, :marca_id, :desc);";
        $stmt = $this->conn->prepare(
            $sql,
            array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
        );
        foreach ($modelos as $id => $desc) {
            $record = array(
                ':id'       => $id,
                ':marca_id' => $marcaId,
                ':desc'     => $desc,
            );
            $stmt->execute($record);
            $record = $this->cleanRecord($record);
            $results[] = $record;
        }

        return $results;
    }

    /**
     * Salva ano/modelo
     *
     * @param array   $anoMods  Ano modelos
     * @param integer $tabelaId Tabela Id
     * @param integer $marcaId  Marca Id
     * @param integer $modeloId Modelo Id
     *
     * @return array
     */
    public function saveAnoModelos(array $anoMods, $tabelaId, $marcaId, $modeloId)
    {
        $results = array();

        $sql = "INSERT INTO anomod (modelo_id, desc, anomod_cod, ano, comb, comb_cod) "."VALUES (:modelo_id, :desc, :anomod_cod, :ano, :comb, :comb_cod);";
        $stmt = $this->conn->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));

        $sqlRef = "INSERT INTO ref_tab_mar_mod_ano (tabela_id, marca_id, modelo_id, anomod_id) "."VALUES (:tabela_id, :marca_id, :modelo_id, :anomod_id);";
        $stmtRef = $this->conn->prepare(
            $sqlRef,
            array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
        );

        foreach ($anoMods as $anoMod => $desc) {
            $tmp      = explode('-', $anoMod);
            $ano      = $tmp[0];
            $combCod  = $tmp[1];
            $comb     = array_key_exists($combCod, self::$combustiveis)
                      ? self::$combustiveis[$combCod]
                      : 0;
            $record = array(
                ':modelo_id'  => $modeloId,
                ':desc'       => $desc,
                ':anomod_cod' => $anoMod,
                ':ano'        => $ano,
                ':comb'       => $comb,
                ':comb_cod'   => $combCod,
            );
            $stmt->execute($record);
            $record = $this->cleanRecord($record);
            $record['id'] = $this->conn->lastInsertId();
            $results[] = $record;

            $stmtRef->execute(
                array(
                    ':tabela_id' => $tabelaId,
                    ':marca_id'  => $marcaId,
                    ':modelo_id' => $modeloId,
                    ':anomod_id' => $record['id'],
                )
            );
        }

        return $results;
    }

    /**
     * Salva veiculos
     *
     * @param array  $veiculos Veiculos
     * @param string $anoModId Ano Modelo Id
     *
     * @return array
     */
    public function saveVeiculos(array $veiculos, $anoModId)
    {
        $results = array();

        $sql = "INSERT INTO veiculo (fipe_cod, tabela_id, marca_id, anomod_id, tipo, modelo, comb_cod, comb_sigla, comb, valor) "."VALUES (:fipe_cod, :tabela_id, :marca_id, :anomod_id, :tipo, :modelo, :comb_cod, :comb_sigla, :comb, :valor);";
        $stmt = $this->conn->prepare(
            $sql,
            array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
        );
        foreach ($veiculos as $id => $veiculo) {
            $record = array(
                ':fipe_cod'   => $veiculo['fipe_cod'],
                ':tabela_id'  => $veiculo['tabela_id'],
                ':marca_id'   => $veiculo['marca_id'],
                ':anomod_id'  => $anoModId,
                ':tipo'       => $veiculo['tipo'],
                ':modelo'     => $veiculo['modelo'],
                ':comb_cod'   => $veiculo['comb_cod'],
                ':comb_sigla' => $veiculo['comb_sigla'],
                ':comb'       => $veiculo['comb'],
                ':valor'      => $veiculo['valor'],
            );
            $stmt->execute($record);

            $record = $this->cleanRecord($record);
            $record['id'] = $this->conn->lastInsertId();
            $results[]    = $record;
        }

        return $results;
    }

    /**
     * Salva veiculos completos
     *
     * @param array $veiculos Veiculos
     *
     * @return array
     */
    public function saveVeiculoCompletos(array $veiculos)
    {
        $results = array();

        $sql = "INSERT INTO veiculo_completo (fipe_cod, tabela_id, anoref, mesref, tipo, marca_id, marca, modelo_id, modelo, anomod, comb_cod, comb_sigla, comb, valor) "."VALUES (:fipe_cod, :tabela_id, :anoref, :mesref, :tipo, :marca_id, :marca, :modelo_id, :modelo, :anomod, :comb_cod,:comb_sigla, :comb, :valor);";
        $stmt = $this->conn->prepare(
            $sql,
            array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
        );
        foreach ($veiculos['results'] as $veiculo) {
            try {
                $record = $this->prepareParameters($veiculo);
                $stmt->execute($record);
                $veiculo['id'] = $this->conn->lastInsertId();
                $results[]     = $veiculo;
            } catch (\Exception $e) {
                // Ignore
            }
        }

        return $results;
    }

    /**
     * Encontra registro
     *
     * @param string $anoref Ano referência
     * @param string $mesref Mês referência
     * @param string $tipo   Tipo
     *
     * @return array
     */
    public function findVeiculos($anoref, $mesref, $tipo)
    {
        $sql = "SELECT * FROM veiculo_completo "." WHERE anoref = :anoref AND mesref = :mesref AND tipo = :tipo;";
        $stmt = $this->conn->prepare(
            $sql,
            array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
        );

        $stmt->execute(
            array(
                ':anoref' => (int) $anoref,
                ':mesref' => (int) $mesref,
                ':tipo'   => (int) $tipo,
            )
        );

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Recupera cabeçalho para CSV
     *
     * @param array   $row       Linha de registro
     * @param boolean $noId      Sem Id
     * @param string  $separator Separador
     *
     * @return string
     */
    public function getCsvHeader($row, $noId = false, $separator = ',')
    {
        $row = $this->getCsvHeaderArray($row, $noId);

        return implode($separator, $row);
    }

    /**
     * Recupera cabeçalho para CSV em array
     *
     * @param array   $row  Linha de registro
     * @param boolean $noId Sem Id
     *
     * @return array
     */
    public function getCsvHeaderArray(array $row, $noId = false)
    {
        if ($noId) {
            unset($row['id']);
        }

        return array_keys($row);
    }

    /**
     * Prepara linha para CSV
     *
     * @param array   $row       Linha de registro
     * @param boolean $noId      Sem Id
     * @param string  $separator Separador
     *
     * @return array
     */
    public function prepareCsvRow($row, $noId = false, $separator = ',')
    {
        if ($noId) {
            unset($row['id']);
        }

        return implode($separator, $row);
    }

    /**
     * Recupera tabelas
     *
     * @return array
     */
    public function findTabelaAtual()
    {
        $sql = "SELECT DISTINCT tabela_id, anoref, mesref, tipo FROM veiculo_completo ORDER BY anoref DESC, mesref DESC, tipo limit 1";
        $stmt = $this->conn->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $stmt->execute();
        $tabelasResult = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $tabelasResult;
    }

    /**
     * Recupera tabelas
     *
     * @return array
     */
    public function findTabelas()
    {
        $sql = "SELECT DISTINCT tabela_id, anoref, mesref, tipo FROM veiculo_completo ORDER BY anoref DESC, mesref DESC, tipo";
        $stmt = $this->conn->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
        $stmt->execute();
        $tabelasResult = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $mesesFlip = array_flip(self::$meses);
        $tabelas = array();
        foreach ($tabelasResult as $tab) {
            $mesref    = str_pad($tab['mesref'], 2, '0', STR_PAD_LEFT);
            $mesref    = $mesesFlip[$mesref];
            $tabelas[] = array(
                'id'  => $tab['tabela_id'].'-'.$tab['tipo'],
                'lbl' => "{$mesref}/{$tab['anoref']} - ".self::$tipos[$tab['tipo']],
            );
        }

        return array('results' => $tabelas);
    }

    /**
     * Encontra veículo por tabela e tipo
     *
     * @param integer $tabela A tabela
     * @param string  $tipo   O tipo
     *
     * @return array
     */
    public function findVeiculosByTabelaAndTipo($tabela, $tipo)
    {
        $sql = "SELECT * FROM veiculo_completo WHERE tabela_id = :tabela_id AND tipo = :tipo";
        $stmt = $this->conn->prepare(
            $sql,
            array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY)
        );
        $stmt->execute(
            array(
                ':tabela_id'  => $tabela,
                ':tipo'       => $tipo,
            )
        );
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array(
            'results' => $results,
            'header'  => $this->getCsvHeaderArray($results[0]),
        );
    }

    /**
     * Limpa registro
     *
     * @param array $record Registro
     *
     * @return array
     */
    protected function cleanRecord(array $record)
    {
        foreach ($record as $id => $value) {
            $newId = substr($id, 1);
            $record[$newId] = $value;
            unset($record[$id]);
        }

        return $record;
    }

    /**
     * Prepara registro
     *
     * @param array $record Registro
     *
     * @return array
     */
    protected function prepareParameters(array $record)
    {
        foreach ($record as $id => $value) {
            $newId = ":{$id}";
            $record[$newId] = $value;
            unset($record[$id]);
        }

        return $record;
    }
}

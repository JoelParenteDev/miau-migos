<?php

class Conexao {

    private static $conexao = null;

    public static function conectar() {

        if (self::$conexao == null) {

            $host = "localhost";
            $banco = "foco";
            $usuario = "root";
            $senha = "";

            try {

                self::$conexao = new PDO(
                    "mysql:host=$host;dbname=$banco;charset=utf8mb4",
                    $usuario,
                    $senha
                );

                self::$conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch (PDOException $e) {

                die("Erro ao conectar: " . $e->getMessage());

            }

        }

        return self::$conexao;
    }

}
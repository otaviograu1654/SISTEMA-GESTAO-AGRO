<?php

$conn = new mysqli("localhost","root","","sga_pecuaria");

$descricao = $_POST['descricao'];
$valor = $_POST['valor'];
$data_vencimento = $_POST['data_vencimento'];
$natureza = $_POST['natureza'];
$prioridade = $_POST['prioridade'];

$sql = "INSERT INTO tabelacontas 
(descricao,valor,data_vencimento,natureza,prioridade)
VALUES 
('$descricao','$valor','$data_vencimento','$natureza','$prioridade')";

$conn->query($sql);

header("Location: contas_a_pagar.php");
exit();

?>
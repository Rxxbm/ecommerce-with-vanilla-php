<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Portal de Produtos</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
  </head>
  <body>
    <div class="container mt-4">
      <h1 class="mb-4 text-center">Portal de Produtos</h1>

      <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php
        // Array de produtos simulando um banco de dados
        $produtos = [
          [
            "nome" =>
        "Produto 1", "descricao" => "Descrição do produto 1", "preco" => 49.90,
        "imagem" => "https://via.placeholder.com/150" ], [ "nome" => "Produto
        2", "descricao" => "Descrição do produto 2", "preco" => 89.90, "imagem"
        => "https://via.placeholder.com/150" ], [ "nome" => "Produto 3",
        "descricao" => "Descrição do produto 3", "preco" => 129.90, "imagem" =>
        "https://via.placeholder.com/150" ], ]; foreach ($produtos as $produto):
        ?>
        <div class="col">
          <div class="card h-100">
            <img
              src="<?= $produto['imagem'] ?>"
              class="card-img-top"
              alt="<?= $produto['nome'] ?>"
            />
            <div class="card-body">
              <h5 class="card-title"><?= $produto['nome'] ?></h5>
              <p class="card-text"><?= $produto['descricao'] ?></p>
              <p class="card-text">
                <strong
                  >R$
                  <?= number_format($produto['preco'], 2, ',', '.') ?></strong
                >
              </p>
            </div>
            <div class="card-footer text-center">
              <a href="#" class="btn btn-primary">Ver mais</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>

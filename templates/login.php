<section class="entrada">
  <div class="selo" aria-hidden="true">♥</div>
  <h1>Acolhe<b>+</b></h1>
  <p class="sub">Sua chegada à ONG, tudo em um só lugar.</p>
  <form method="post" action="index.php?r=login" class="cartao">
    <?= csrf($csrf) ?>
    <label>E-mail <input type="email" name="email" required autocomplete="username" value="<?= e($email ?? '') ?>"></label>
    <label>Senha <input type="password" name="senha" required autocomplete="current-password"></label>
    <button class="btn primario" type="submit">Entrar</button>
  </form>
</section>

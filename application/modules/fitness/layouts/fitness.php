<?php

/** @var \Ilch\Layout\Frontend $this */
?>
<!DOCTYPE html>
<html lang="<?=$this->escape(substr($this->getTranslator()->getLocale(), 0, 2)) ?>">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?=$this->getHeader() ?>
        <link href="<?=$this->getModuleUrl('static/css/fitness.css') ?>" rel="stylesheet">
        <?=$this->getCustomCSS() ?>
        <script src="<?=$this->getVendorUrl('twbs/bootstrap/dist/js/bootstrap.bundle.min.js') ?>"></script>
    </head>
    <body class="fx-body">
        <header class="fx-header">
            <nav class="navbar navbar-expand-md navbar-dark">
                <div class="container">
                    <a class="navbar-brand fx-brand" href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'index', 'action' => 'index']) ?>">
                        <i class="fa-solid fa-dumbbell"></i> <?=$this->getTrans('menuFitness') ?>
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#fxNav" aria-controls="fxNav" aria-expanded="false" aria-label="<?=$this->getTrans('toggleNavigation') ?>">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="fxNav">
                        <ul class="navbar-nav me-auto">
                            <li class="nav-item">
                                <a class="nav-link<?=$this->getRequest()->getControllerName() === 'index' ? ' active' : '' ?>" href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'index', 'action' => 'index']) ?>"><?=$this->getTrans('navDashboard') ?></a>
                            </li>
                        </ul>
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="<?=$this->getUrl() ?>"><i class="fa-solid fa-arrow-left"></i> <?=$this->getTrans('backToWebsite') ?></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>

        <main class="fx-main">
            <div class="container">
                <?=$this->getContent() ?>
            </div>
        </main>

        <footer class="fx-footer">
            <div class="container">
                <a href="<?=$this->getUrl(['module' => 'imprint', 'controller' => 'index', 'action' => 'index']) ?>"><?=$this->getTrans('imprint') ?></a>
                <a href="<?=$this->getUrl(['module' => 'privacy', 'controller' => 'index', 'action' => 'index']) ?>"><?=$this->getTrans('privacy') ?></a>
            </div>
        </footer>

        <?=$this->getFooter() ?>
    </body>
</html>

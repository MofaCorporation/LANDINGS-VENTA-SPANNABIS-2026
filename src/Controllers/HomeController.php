<?php

declare(strict_types=1);

namespace App\Controllers;

final class HomeController extends BaseController
{
    public function index(): void
    {
        $this->render('home', [
            'pageTitleKey'       => 'site.default_title',
            'metaDescriptionKey' => 'site.default_title',
            'htmlThemeStyle'     => '',
            'extraModuleSrc'     => null,
        ]);
    }
}

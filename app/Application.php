<?php
namespace App;


/**
 * Esta classe redefine o caminho da pasta 'public_html' para 'www'.
 * Requer alteração no arquivo bootstrap/app.php. Comando:
 *      $app = new MyApp\Application(
 *         realpath(__DIR__.'/../')
 *      );
 */
class Application extends \Illuminate\Foundation\Application {
    public function publicPath()
    {
        return $this->basePath.DIRECTORY_SEPARATOR.env('DIR_PUBLIC');
    }
}
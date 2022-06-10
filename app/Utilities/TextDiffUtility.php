<?php

namespace App\Utilities;

use Jfcherng\Diff\Differ;
use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Factory\RendererFactory;
use Jfcherng\Diff\Renderer\RendererConstant;

/**
 * Classe para encontrar diferenças entre dois textos
 * https://github.com/jfcherng/php-diff
 */
class TextDiffUtility{
    
    private static $rendererName = 'Unified';
    
    private static $differOptions = [
        // show how many neighbor lines
        // Differ::CONTEXT_ALL can be used to show the whole file
        'context' => 3,
        // ignore case difference
        'ignoreCase' => false,
        // ignore whitespace difference
        'ignoreWhitespace' => false,
    ];
    
    // the renderer class options
    private static $rendererOptions = [
        // how detailed the rendered HTML in-line diff is? (none, line, word, char)
        'detailLevel' => 'char',
        // renderer language: eng, cht, chs, jpn, ...
        // or an array which has the same keys with a language file
        'language' => 'eng',
        // show line numbers in HTML renderers
        'lineNumbers' => false,
        // show a separator between different diff hunks in HTML renderers
        'separateBlock' => true,
        // show the (table) header
        'showHeader' => false,
        // the frontend HTML could use CSS "white-space: pre;" to visualize consecutive whitespaces
        // but if you want to visualize them in the backend with "&nbsp;", you can set this to true
        'spacesToNbsp' => false,
        // HTML renderer tab width (negative = do not convert into spaces)
        'tabSize' => 4,
        // this option is currently only for the Combined renderer.
        // it determines whether a replace-type block should be merged or not
        // depending on the content changed ratio, which values between 0 and 1.
        'mergeThreshold' => 0.8,
        // this option is currently only for the Unified and the Context renderers.
        // RendererConstant::CLI_COLOR_AUTO = colorize the output if possible (default)
        // RendererConstant::CLI_COLOR_ENABLE = force to colorize the output
        // RendererConstant::CLI_COLOR_DISABLE = force not to colorize the output
        'cliColorization' => RendererConstant::CLI_COLOR_AUTO,
        // this option is currently only for the Json renderer.
        // internally, ops (tags) are all int type but this is not good for human reading.
        // set this to "true" to convert them into string form before outputting.
        'outputTagAsString' => false,
        // this option is currently only for the Json renderer.
        // it controls how the output JSON is formatted.
        // see available options on https://www.php.net/manual/en/function.json-encode.php
        'jsonEncodeFlags' => \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
        // this option is currently effective when the "detailLevel" is "word"
        // characters listed in this array can be used to make diff segments into a whole
        // for example, making "<del>good</del>-<del>looking</del>" into "<del>good-looking</del>"
        // this should bring better readability but set this to empty array if you do not want it
        'wordGlues' => [' ', '-'],
        // change this value to a string as the returned diff if the two input strings are identical
        'resultForIdenticals' => null,
        // extra HTML classes added to the DOM of the diff container
        'wrapperClasses' => ['diff-wrapper'],
    ];
  
    public static function toHTML($old,$new){
        // one-line simply compare two strings
        //return DiffHelper::calculate($old, $new, self::$rendererName, self::$differOptions, self::$rendererOptions);
        //return DiffHelper::calculate($old, $new, self::$rendererName);
        
        //$differ = new Differ(explode("\n", $old), explode("\n", $new), self::$differOptions);
        //$renderer = RendererFactory::make(self::$rendererName, self::$rendererOptions); // or your own renderer object
        //return $renderer->render($differ);
        
        // use the JSON result to render in HTML
        $jsonResult = DiffHelper::calculate($old, $new, 'Json'); // may store the JSON result in your database
        $htmlRenderer = RendererFactory::make('Inline', self::$rendererOptions);
        return $htmlRenderer->renderArray(json_decode($jsonResult, true));
    }
    
    
    public static function css($tag_style=false){
        $r='
.diff-wrapper.diff {
  background: repeating-linear-gradient(-45deg, whitesmoke, whitesmoke 0.5em, #e8e8e8 0.5em, #e8e8e8 1em);
  border-collapse: collapse;
  border-spacing: 0;
  border: 0px solid #ddd;
  color: black;
  empty-cells: show;
  font-family: monospace;
  font-size: 13px;
  width: 100%;
  word-break: break-all;
}
.diff-wrapper.diff th {
  font-weight: 700;
}
.diff-wrapper.diff td {
  vertical-align: baseline;
}
.diff-wrapper.diff td,
.diff-wrapper.diff th {
  border-collapse: separate;
  border: none;
  padding: 1px 2px;
  background: #fff;
}
.diff-wrapper.diff td:empty:after,
.diff-wrapper.diff th:empty:after {
  content: " ";
  visibility: hidden;
}
.diff-wrapper.diff td a,
.diff-wrapper.diff th a {
  color: #000;
  cursor: inherit;
  pointer-events: none;
}
.diff-wrapper.diff thead th {
  background: #a6a6a6;
  border-bottom: 1px solid black;
  padding: 4px;
  text-align: left;
}
.diff-wrapper.diff tbody.skipped {
  border-top: 1px solid black;
}
.diff-wrapper.diff tbody.skipped td,
.diff-wrapper.diff tbody.skipped th {
  display: none;
}
.diff-wrapper.diff tbody th {
  background: #cccccc;
  border-right: 1px solid black;
  text-align: right;
  vertical-align: top;
  width: 4em;
}
.diff-wrapper.diff tbody th.sign {
  background: #fff;
  border-right: none;
  padding: 1px 0;
  text-align: center;
  width: 1em;
}
.diff-wrapper.diff tbody th.sign.del {
  background: #fbe1e1;
}
.diff-wrapper.diff tbody th.sign.ins {
  background: #e1fbe1;
}
.diff-wrapper.diff.diff-html {
  white-space: pre-wrap;
}
.diff-wrapper.diff.diff-html.diff-combined .change.change-rep .rep {
  white-space: normal;
}
.diff-wrapper.diff.diff-html .change.change-eq .old,
.diff-wrapper.diff.diff-html .change.change-eq .new {
  background: #fff;
}
.diff-wrapper.diff.diff-html .change .old {
  background: #fbe1e1;
}
.diff-wrapper.diff.diff-html .change .new {
  background: #e1fbe1;
}
.diff-wrapper.diff.diff-html .change .rep {
  background: #fef6d9;
}
.diff-wrapper.diff.diff-html .change .old.none,
.diff-wrapper.diff.diff-html .change .new.none,
.diff-wrapper.diff.diff-html .change .rep.none {
  background: transparent;
  cursor: not-allowed;
}
.diff-wrapper.diff.diff-html .change ins,
.diff-wrapper.diff.diff-html .change del {
  font-weight: bold;
  text-decoration: none;
}
.diff-wrapper.diff.diff-html .change ins {
  background: #94f094;
}
.diff-wrapper.diff.diff-html .change del {
  background: #f09494;
}
';
        
        if($tag_style)$r='<style>'.$r.'</style>';
        return $r;
    }
}

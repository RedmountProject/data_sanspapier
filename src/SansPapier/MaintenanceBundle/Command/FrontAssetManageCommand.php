<?php
/*  Copyright (C) 2013 NUNJE Aymeric
    Copyright (C) 2013 BRISOU Amaury

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
namespace SansPapier\MaintenanceBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\Yui\CssCompressorFilter;
use Assetic\Filter\Yui\JsCompressorFilter;

class FrontAssetManageCommand extends ContainerAwareCommand {

    private $output;
    private $html_css_path;
    private $html_js_path;
    private $front_path;
    private $versionNumber;
    private $toDeleteVersionNumber;

    protected function configure() {
        $this->setName('sanspapier:assets')
                ->setDescription('Task to compile CSS and JS in minified version for the front');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;

        $output->writeln("Locating root file directory.");
        $this->front_path = $this->getContainer()->get('kernel')->getRootDir() . "/../../front_sanspapier";

        if (!file_exists($this->front_path)) {
            $output->writeln("front_sanspapier not on the server");
            return;
        }
        $output->writeln("Found : front_sanspapier  at " . $this->front_path);
        
        try {
            $this->manageVersion();
        } catch(\Exception $e) {
            $output->writeln("Error while managing versionNumber file : " . $e);
            return;
        }
        
        $this->html_css_path = $this->front_path . "/html4/css";
        $this->html_js_path = $this->front_path . "/html4/js";
        
        $output->writeln("Continuing ...");
        $output->writeln("Begin minifying CSS.");
        $this->minify_css();

        $output->writeln("Minifying CSS successfully done.\n");
        $output->writeln("Begin minifying JS.\n");
        $this->minify_js();
        $output->writeln("Minifying JS successfully done.\n");
        
        $output->writeln("Begin deleting old CSS.\n");
        $this->delete_css();
        $output->writeln("Deleting old CSS successfully done.\n");
        
        $output->writeln("Begin deleting old JS.\n");
        $this->delete_js();
        $output->writeln("Deleting old JS successfully done.\n");
        
        $output->writeln("Process Ending without error");
    }
    
    private function manageVersion() {
        $versionDir = $this->getContainer()->get('kernel')->getRootDir() . '/../web/version';
        try {
            $versionDirHandle = dir($versionDir);
            $oldVersionNumber = '';
            while($versionFile = $versionDirHandle->read()) {
                if($versionFile != '.' && $versionFile != '..')
                    $oldVersionNumber = basename($versionFile);
            }
            $versionDirHandle->close();
            $this->versionNumber = $oldVersionNumber + 1;
            $newVersionFile = fopen($versionDir.'/'.$this->versionNumber,"w");
            fclose($newVersionFile);
            unlink($versionDir.'/'.$oldVersionNumber);
            $this->toDeleteVersionNumber = $oldVersionNumber - 1;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function minify_css() {

        $this->minify_global_css();
        $this->minify_search_html5_css();
        $this->minify_search_html4_css();

        $shop_css_files = array(
            'shop.css'
        );

        $this->minify_specific_css_file($shop_css_files, 'shop');

        $about_css_files = array(
            'about.css'
        );

        $this->minify_specific_css_file($about_css_files, 'about');

        $help_css_files = array(
            'aide_en_ligne.css'
        );

        $this->minify_specific_css_file($help_css_files, 'help');


        $profile_css_files = array(
            'autocomplete.css',
            'profile.css'
        );

        $this->minify_specific_css_file($profile_css_files, 'profile');
    }

    private function minify_global_css() {
        $css_result_path = $this->getContainer()->get('kernel')->getRootDir() . '/../web/css/global_comp_'.$this->versionNumber.'.css';

        $global_css_files = array(
            'reset.css',
            'jquery.qtip.css',
            'font.css',
            'color.css',
            'grid_system.css',
            'global.css',
            'windows.css',
            'fiche_livre.css',
            'cart.css'
            
        );

        $this->concat_css($global_css_files, $css_result_path);
    }

    private function minify_search_html5_css() {
        $css_result_path = $this->getContainer()->get('kernel')->getRootDir() . '/../web/css/search_html5_comp_'.$this->versionNumber.'.css';
        $this->output->writeln("compress search css html5 located at " . $css_result_path);


        $css_files = array(
            'autocomplete_search.css',
            'categories.css',
            'search.css',
            'cible_hover_easel.css'
        );

        $this->concat_css($css_files, $css_result_path);
    }

    private function minify_search_html4_css() {

        $search_css_files = array(
            'categories.css',
            'search.css',
            'cible_hover.css'
        );

        $css_result_path = $this->getContainer()->get('kernel')->getRootDir() . '/../web/css/search_html4_comp_'.$this->versionNumber.'.css';
        $this->output->writeln("compress search css html4 located at " . $css_result_path);

        $this->concat_css($search_css_files, $css_result_path);
    }

    private function minify_specific_css_file($css_files, $filename) {

        $css_result_path = $this->getContainer()->get('kernel')->getRootDir() . '/../web/css/' . $filename . '_comp_'.$this->versionNumber.'.css';
        $this->output->writeln("compress ".$filename." css html4 located at " . $css_result_path);

        $this->concat_css($css_files, $css_result_path);
    }

    private function minify_js() {

        $this->minify_global_js();
        $this->minify_libs_js();
        $this->minify_specific_js_pages();
    }

    private function minify_global_js() {
        $js_global_files = array(
            'sp_libs.js',
            'context.js',
            'config.js'
        );
        $js_global_result_path = $this->getContainer()->get('kernel')->getRootDir() . '/../web/js/global_comp_'.$this->versionNumber.'.js';


        $this->concat_js($js_global_files, $js_global_result_path);
    }

    private function minify_libs_js() {
        $js_libs_files = array(
            'LAB.min.js',
            'jquery.js',
            'jquery_animate_color.js',
            'jquery.event.drag-2.2.js',
            'jquery.qtip.js',
            'jquery.example.js',
            'jquery.address.js'
        );

        $js_libs_result_path = $this->getContainer()->get('kernel')->getRootDir() . '/../web/js/libs_comp.js';

        $this->concat_js($js_libs_files, $js_libs_result_path);
    }

    private function concat_css($css_files, $css_result_path) {
        $css_path = array();
        foreach ($css_files as $file) {
            $css_path[] = new FileAsset($this->html_css_path . "/" . $file);
        }
        $this->output->writeln("compress global css to " . $css_result_path);
        $resource = new AssetCollection(
                        $css_path,
                        array(
                            new CssCompressorFilter($this->getContainer()->get('kernel')->getRootDir() . '/../bin/yuicompressor-2.4.7.jar')
                        )
        );
        $resource->load();
        // css
        file_put_contents($css_result_path, $resource->dump());
    }

    private function concat_js($js_global_files, $js_global_result_path) {

        $js_global_path = array();
        foreach ($js_global_files as $file) {
            $js_global_path[] = new FileAsset($this->html_js_path . "/" . $file);
        }

        $this->output->writeln("compress global js to " . $js_global_result_path);
        $resource = new AssetCollection(
                        $js_global_path,
                        array(
                            new JsCompressorFilter($this->getContainer()->get('kernel')->getRootDir() . '/../bin/yuicompressor-2.4.7.jar')
                        )
        );
        $resource->load();
        // global js
        file_put_contents($js_global_result_path, $resource->dump());
    }

    private function minify_specific_js_pages() {
        $js_files = array(
            array(
                "dirname" => "search",
                "filename" => "search_html5",
                array(
                    '../jquery-ui-1.9.2.custom.min.js',
                    '../dynamic_tutorial.js',
                    'ui.js',
                    'tooltips.js',
                    'categories.js',
                    'scroll_fixed.js',
                    'json.js',
                    'form.js',
                    '../easeljs-0.6.1.min.js',
                    '../tweenjs-0.4.1.min.js',
                    'easel/book.js',
                    'easel/book_container.js',
                    'easel/cible_easel.V2.0.js',
                    'ajax.js',
                    'init.js',
                ),
            ),
            array(
                "dirname" => "search",
                "filename" => "search_html4",
                array(
                    '../dynamic_tutorial.js',
                    'ui.js',
                    'tooltips.js',
                    'categories.js',
                    'scroll_fixed.js',
                    'json.js',
                    'form.js',
                    'raphael.js',
                    'cible.js',
                    'ajax.js',
                    'init.js'
                ),
            ),
            array(
                "dirname" => "profile",
                "filename" => "profile",
                array(
                    '../jquery-ui-1.8.23.custom.min.js',
                    'categories.js',
                    'init.js',
                    'json.js',
                    'ajax.js',
                    'tooltips.js',
                    'form.js'
                ),
            ),
            array(
                "dirname" => "shop",
                "filename" => "shop",
                array(
                    'form.js',
                    'cart.js',
                    'json.js',
                    'init.js',
                    'ajax.js'
                ),
            ),
            array(
                "dirname" => "aide_en_ligne",
                "filename" => "aide_en_ligne",
                array(
                    'ajax.js',
                    'categories.js',
                    'form.js',
                    'json.js',
                    'tooltips.js',
                    'init.js'
                ),
            ),
            array(
                "dirname" => "a_propos",
                "filename" => "a_propos",
                array(
                    'form.js',
                    'init.js',
                    'ajax.js',
                    'json.js',
                    'tooltips.js',
                    'categories.js'
                )
            )
        );

        foreach ($js_files as $entry) {
            $dirname = $entry["dirname"];
            $filename = $entry["filename"];

            $res = $this->getContainer()->get('kernel')->getRootDir() . '/../web/js/' . $filename . '_comp_'.$this->versionNumber.'.js';
            $this->output->writeln("compress " . $dirname . " js to " . $res);

            $js_assets = array();
            foreach ($entry[0] as $file) {
                $filepath = $this->html_js_path . '/' . $dirname . '/' . $file;
                $js_assets[] = new FileAsset($filepath);
            }
            $resource = new AssetCollection(
                            $js_assets,
                            array(
                                new JsCompressorFilter($this->getContainer()->get('kernel')->getRootDir() . '/../bin/yuicompressor-2.4.7.jar')
                            )
            );
            $resource->load();
            file_put_contents($res, $resource->dump());
        }
    }
    
    private function delete_js() {
        $directory = $this->getContainer()->get('kernel')->getRootDir() . '/../web/js/';
        $js_files = array("search_html5_comp_".$this->toDeleteVersionNumber.".js",
                          "search_html4_comp_".$this->toDeleteVersionNumber.".js",
                          "profile_comp_".$this->toDeleteVersionNumber.".js",
                          "shop_comp_".$this->toDeleteVersionNumber.".js",
                          "aide_en_ligne_comp_".$this->toDeleteVersionNumber.".js",
                          "a_propos_comp_".$this->toDeleteVersionNumber.".js",
                          "global_comp_".$this->toDeleteVersionNumber.".js");
        foreach($js_files as $js_file) {
            unlink($directory.$js_file);
        }
    }
    
    private function delete_css() {
        $directory = $this->getContainer()->get('kernel')->getRootDir() . '/../web/css/';
        $css_files = array("about_comp_".$this->toDeleteVersionNumber.".css",
                          "global_comp_".$this->toDeleteVersionNumber.".css",
                          "help_comp_".$this->toDeleteVersionNumber.".css",
                          "profile_comp_".$this->toDeleteVersionNumber.".css",
                          "search_html4_comp_".$this->toDeleteVersionNumber.".css",
                          "search_html5_comp_".$this->toDeleteVersionNumber.".css",
                          "shop_comp_".$this->toDeleteVersionNumber.".css");
        foreach($css_files as $css_file) {
            unlink($directory.$css_file);
        }
    }

}

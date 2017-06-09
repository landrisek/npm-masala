<?php

namespace Masala;

use Nette\Application\UI\Presenter,
    Nette\Forms\Controls\TextArea,
    Nette\Utils\Html;

/** @author Lubomir Andrisek */
final class WysiwygEditor extends TextArea {

    /** @var string type */
    private $type;

    /** @var Integer */
    private $version = 4;

    /** @var Array */
    private $_filters = [];

    /** @var Presenter */
    private $parent;

    public function __construct($label, $parent, $cols = 40, $rows = 10, $type = 'all') {
        parent::__construct($label);
        $this->control->setName('textarea');
        $this->control->cols = $cols;
        $this->control->rows = $rows;
        $this->value = '';
        $this->type = $type;
        $this->parent = $parent;
    }

    /** getters */
    public function getControl() {
        $container = Html::el();
        $container->addHtml(parent::getControl()->style("width: 100%;"));
        $uri = $this->parent->getContext()->getByType('Nette\Http\Request')->url->baseUrl;
        $path = $uri;
        $script = Html::el();
        $ckeditor = $this->version == 3 ? 'assets/masala/ckeditor3/ckeditor.js' : 'node_modules/ckeditor/ckeditor.js';
        $plugins = $this->version == 3 ? 'extraPlugins : "report",' : '';
        //$script->setHtml('<script type="text/javascript" src="' . $path . $ckeditor . '"></script>
        $script->setHtml('<script type="text/javascript">
                CKEDITOR.replace( "' . $this->getHtmlId() . '",
                    {
                        ' . $plugins . '
                        allowedContent: true,
                        toolbar :
                            [
                                { name: "clipboard", items : [ "Cut","Copy","Paste","PasteText","PasteFromWord","-","Undo","Redo" ] },
                                { name: "basicstyles", items : [ "Bold","Italic","Strike","Subscript","Superscript","-","RemoveFormat" ] } ,
                                { name: "insert", items : [ "Image","Table","HorizontalRule" ] },
                                { name: "styles", items : [ "Styles","Format","-","JustifyLeft","JustifyCenter","JustifyRight","JustifyRight" ] },
                                "/",
                                { name: "paragraph", items : [ "NumberedList","BulletedList","-","Outdent","Indent","-","Blockquote" ] },
                                { name: "links", items : [ "Link","Unlink", "Anchor" ] },
                                { name: "report", items : [ "report" ] },
                                { name: "colors",      items : [ "TextColor","BGColor" ] },
                                { name: "api", items : [ "Source" ] },
                            ]
                    });
            </script>
        ');
        /** filters */
        $container->addHtml($script);
        $filters = '<ul class="texts" style="display:none;">';
        foreach ($this->_filters as $filter) {
            $filters .= '<li class="' . $filter['key'] . '.Text">' . $filter['name'] . '</li>';
        }
        $filters .= '</ul>';
        /** sentences */
        $filters .= '<ul class="sentences" style="display:none;">';
        foreach ($this->_filters as $filter) {
            $filters .= '<li class="' . $filter['key'] . '.Sentence">' . $filter['name'] . '</li>';
        }
        $filters .= '</ul>';
        /** sentences */
        $filters .= '<ul class="words" style="display:none;">';
        foreach ($this->_filters as $filter) {
            $filters .= '<li class="' . $filter['key'] . '.Word">' . $filter['name'] . '</li>';
        }
        $filters .= '</ul>';
        /** offset */
        $filters .= '<ul class="offset" style="display:none;">';
        for ($i = 1; $i < 100; $i++) {
            $filters .= '<li class="offset.' . $i . '">' . $i . '</li>';
        }
        $filters .= '</ul>';
        /** inflection */
        $inflections = get_object_vars(new InflectVO());
        $inflectList = '<ul class="inflection" style="display:none;">';
        foreach ($inflections as $fall => $value) {
            $inflectList .= '<li class="' . $fall . '">' . $fall . '</li>';
        }
        $inflectList .= '</ul>';
        $container->addHtml($filters);
        $container->addHtml($inflectList);
        return $container;
    }

    public function getFilters() {
        return $this->_filters;
    }

    public function getValue() {
        return html_entity_decode(parent::getValue(), ENT_QUOTES, "utf-8");
    }

    private function get_html_translation_table_CP1252() {
        $trans = get_html_translation_table(HTML_ENTITIES);
        $trans[chr(130)] = '&sbquo;';    // Single Low-9 Quotation Mark
        $trans[chr(131)] = '&fnof;';     // Latin Small Letter F With Hook
        $trans[chr(132)] = '&bdquo;';    // Double Low-9 Quotation Mark
        $trans[chr(133)] = '&hellip;';   // Horizontal Ellipsis
        $trans[chr(134)] = '&dagger;';   // Dagger
        $trans[chr(135)] = '&Dagger;';   // Double Dagger
        $trans[chr(136)] = '&circ;';     // Modifier Letter Circumflex Accent
        $trans[chr(137)] = '&permil;';   // Per Mille Sign
        $trans[chr(138)] = '&Scaron;';   // Latin Capital Letter S With Caron
        $trans[chr(139)] = '&lsaquo;';   // Single Left-Pointing Angle Quotation Mark
        $trans[chr(140)] = '&OElig;';    // Latin Capital Ligature OE
        $trans[chr(145)] = '&lsquo;';    // Left Single Quotation Mark
        $trans[chr(146)] = '&rsquo;';    // Right Single Quotation Mark
        $trans[chr(147)] = '&ldquo;';    // Left Double Quotation Mark
        $trans[chr(148)] = '&rdquo;';    // Right Double Quotation Mark
        $trans[chr(149)] = '&bull;';     // Bullet
        $trans[chr(150)] = '&ndash;';    // En Dash
        $trans[chr(151)] = '&mdash;';    // Em Dash
        $trans[chr(152)] = '&tilde;';    // Small Tilde
        $trans[chr(153)] = '&trade;';    // Trade Mark Sign
        $trans[chr(154)] = '&scaron;';   // Latin Small Letter S With Caron
        $trans[chr(155)] = '&rsaquo;';   // Single Right-Pointing Angle Quotation Mark
        $trans[chr(156)] = '&oelig;';    // Latin Small Ligature OE
        $trans[chr(159)] = '&Yuml;';     // Latin Capital Letter Y With Diaeresis
        ksort($trans);
        return $trans;
    }

    /** setters */
    public function setFilters($filters) {
        $this->_filters = $filters;
        return $this->getControl();
    }

    public function setVersion($version) {
        $this->version = $version;
        return $this;
    }

}

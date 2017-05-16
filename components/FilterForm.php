<?php

namespace Masala;

use Nette\Application\IPresenter,
    Nette\Application\UI\Control,
    Nette\Localization\ITranslator,
    Nette\Http\IRequest;

/** @author Lubomir Andrisek */
final class FilterForm extends ReactForm implements IFilterFormFactory {

    /** @var IBuilder */
    private $grid;

    /** @var IRequest */
    private $request;

    /** @var TranslatorModel */
    private $translatorModel;

    public function __construct(IRequest $request, ITranslator $translatorModel) {
        parent::__construct($request, $translatorModel);
        $this->request = $request;
        $this->translatorModel = $translatorModel;
    }

    /** @return IFilterFormFactory */
    public function create() {
        return $this;
    }

    /** @return IFilterFormFactory */
    public function setGrid(IBuilder $grid) {
        $this->grid = $grid;
        return $this;
    }

    /** @return array */
    private function getDefaults() {
        $defaults = $this->grid->getDefaults();
        if (null == $spice = json_decode(urldecode($this->request->getUrl()->getQueryParameter(strtolower($this->getParent()->getName()) . '-spice')))) {
            $spice = [];
        }
        foreach ($spice as $key => $grain) {
            $column = preg_replace('/\s(.*)/', '', $key);
            if (preg_match('/\s>/', $key) and isset($defaults[$column]) and is_array($defaults[$column]) and isset($defaults[$column]['>']) and $key == $column . ' >') {
                $defaults[$column]['>'] = $grain;
            } elseif (preg_match('/\s</', $key) and isset($defaults[$column]) and is_array($defaults[$column]) and isset($defaults[$column]['<']) and $key == $column . ' <') {
                    $defaults[$column]['<'] = $grain;
            } elseif (isset($defaults[$column]) and ! is_array($defaults[$column])) {
                $defaults[$column] = $grain;
            }
        }
        return $defaults;
    }


    public function attached($presenter) {
        parent::attached($presenter);
        if ($presenter instanceof IPresenter) {
            foreach ($this->grid->getColumns() as $name => $annotation) {
                $defaults = $this->getDefaults();
                if (true == $this->grid->getAnnotation($name, ['unfilter', 'hidden'])) {                    
                } elseif (true == $this->grid->getAnnotation($name, 'addDate')) {
                    $this->addDateTimePicker($name, []);
                } elseif (true == $this->grid->getAnnotation($name, 'range') or is_array($this->grid->getRange($name))) {
                    $this->addRange($name, $defaults[$name]);
                } elseif (is_array($defaults[$name]) and ! empty($defaults[$name]) and true == $this->grid->getAnnotation($name, 'addMultiSelect')) {
                    $defaults[$name] = [null => $this->translatorModel->translate('--unchosen--')] + $defaults[$name];
                    $this->addMultiSelect($name, ['values'=>$defaults[$name], 'min-width'=>'10px;', 'class'=>'form-control']);
                } elseif (is_array($defaults[$name]) and ! empty($defaults[$name])) {
                    $defaults[$name] = [null => $this->translatorModel->translate('--unchosen--')] + $defaults[$name];
                    $this->addSelect($name, ['values' => $defaults[$name], 
                                            'class' => 'form-control',
                                            'style' => 'height:100%',
                                            'onchange' => 'handleFilter()']);
                } else {
                    $this->addText($name, ['class' => 'form-control']);
                }
            }
        }
    }

    public function submit(Control $control) {
        
    }
    
    public function succeeded(array $data) {
        $parameters = parent::succeeded($data);
        die();
    }

}

interface IFilterFormFactory {

    /** @return FilterForm */
    function create();
}

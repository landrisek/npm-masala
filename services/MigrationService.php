<?php

namespace Masala;

use Nette\Application\UI\Presenter,
    Nette\Database\Table\ActiveRow,
    Nette\InvalidArgumentException;

/** @author Lubomir Andrisek */
final class MigrationService implements IProcessService {

    /** @var MasalaFactory */
    private $masalaFactory;

    /** @var SpiceModel */
    private $spiceModel;

    /** @var ActiveRow */
    private $setting;

    /** @var string */
    private $views;

    public function __construct($views, IMasalaFactory $masalaFactory, SpiceModel $spiceModel) {
        $this->views = (string) $views;
        $this->masalaFactory = $masalaFactory;
        $this->spiceModel = $spiceModel;
    }

    /** getters */
    public function getSetting() {
        return $this->setting;
    }

    public function getView($source) {
        $spice = $this->spiceModel->getSpice($source);
        $views = [];
        foreach($this->spiceModel->getView($spice->query, (array) json_decode($spice->arguments)) as $viewId => $view) {
            $type = gettype($view);
            if(null == $type) {
                $views[$viewId] = 'string';
            } elseif(is_object($type)) {
                $views[$viewId] = 'datetime';
            } else {
                $views[$viewId] = $type;
            }
        }
        return $views;
    }

    /** setters */
    public function setSetting(ActiveRow $setting) {
        $this->setting = $setting;
        return $this;
    }

    public function build(Presenter $presenter, IBuilder $builder, Array $view, $id) {
        $presenter->addComponent($this->masalaFactory->create()->setGrid($builder), 'masalaFactory');
        $builder->attached($this->masalaFactory);
        $builder->filter($view);
        if(false == function_exists('murmurhash3')) {
            throw new InvalidArgumentException('Murmurhash3 token hash for php is not enabled. Install it from https://github.com/lastguest/murmurhash-php.');
        }
        for($i=$view['offset'];$i < 50;$i++) {
            if(is_object($offset = $builder->getOffset($i))) {
                $row = (array) $offset;
                $row['token'] = murmurhash3(implode((array) $row, '|'));
                $this->spiceModel->addView($this->views . '_' . $id, (array) $row);
            }
        }
    }

    /** process methods */
    public function prepare(IMasalaFactory $masala) {
        return $masala->getGrid()->getSum();
    }

    public function run(Array $row, Array $rows, IMasalaFactory $masala) {
        $rows['status'] = 'migration';
        $table = $this->views . '_' . $this->spiceModel->getViewBySource($masala->getPresenter()->getName() . ':' . $masala->getPresenter()->getAction());
        $this->spiceModel->addView($table, $row);
        return $rows;
    }

    public function done(Array $rows, Presenter $presenter) {
        return ['status'=>'migration'];
    }

    public function message(IMasalaFactory $masala) {

    }

}

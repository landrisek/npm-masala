<?php

namespace Masala;

use Nette\Application\UI\Control,
    Nette\Application\IPresenter,
    Nette\Application\Responses\JsonResponse,
    Nette\Database\Table\ActiveRow,
    Nette\Http\IRequest,
    Nette\Localization\ITranslator;

/** @author Lubomir Andrisek */
final class ContentForm extends Control implements IContentFormFactory {

    /** @var IContent */
    private $contentRepository;

    /** @var string */
    private $jsDir;

    /** @var string */
    private $keyword;

    /** @var KeywordsRepository */
    private $keywordsRepository;

    /** @var IPresenter */
    private $presenter;

    /** @var IRequest */
    private $request;

    /** @var IRow */
    private $row;

    /** @var array */
    private $source;

    /** @var ITranslator */
    private $translatorRepository;

    /** @var WriteRepository */
    private $writeRepository;

    public function __construct($jsDir, IContent $contentRepository, IRequest $request, ITranslator $translatorRepository, KeywordsRepository $keywordsRepository, WriteRepository $writeRepository) {
        $this->jsDir = $jsDir;
        $this->contentRepository = $contentRepository;
        $this->writeRepository = $writeRepository;
        $this->request = $request;
        $this->keywordsRepository = $keywordsRepository;
        $this->translatorRepository = $translatorRepository;
    }

    /** @return IContentFormFactory */
    public function create() {
        return $this;
    }

    /** @return void */
    public function attached($presenter) {
        parent::attached($presenter);
        if($presenter instanceof IPresenter) {
            $this->presenter = $presenter;
            $this->keyword = $this->request->getQuery('keyword');
        }
    }

    /** @return void */
    public function handleKeyword() {
        $response = new JsonResponse($this->wildcard($this->request->getPost('keywords'), []));
        $this->presenter->sendResponse($response);
    }

    /** @return void */
    public function handleSubmit() {
        $this->writeRepository->updateWrite($this->keyword, ['content' => $this->request->getPost('content')]);
        $this->presenter->sendPayload();
    }

    /** @return void */
    public function handleWrite() {
        $keywords = $this->request->getPost('keywords');
        $options = $this->request->getPost('options');
        $wildcards = $this->request->getPost('wildcards');
        $write = $this->request->getPost('write');
        $max = 0;
        /** solved by name */
        foreach ($options as $optionId => $option) {
            $summary = substr_count($keywords, $option);
            if ($summary > $max) {
                $max = $summary;
                $selected = $optionId;
            }
        }
        /** solved by database */
        if (0 == $max && strlen($write) > 0) {
            foreach ($options as $optionId => $option) {
                if(sizeof($like = $this->wildcard($option, $wildcards)) > sizeof($wildcards)) {
                    $summary = $this->contentRepository->getKeywords($like);
                    if ($summary > $max) {
                        $max = $summary;
                        $selected = $optionId;
                    }
                }
            }
        }
        if (!isset($selected)) {
            $selected = array_rand($options);
        }
        $response = new JsonResponse(['keywords' => $keywords, 'option' => $selected, 'options' => $options, 'max' => $max]);
        $this->presenter->sendResponse($response);
    }

    /** @return void */
    public function render(...$args) {
        $this->template->component = $this->getName();
        $this->template->data =  json_encode(['content' => json_decode(trim($this->row->check()->content)),
                                            'edit'=>-1,
                                            'labels'=>['select'=>ucfirst($this->translatorRepository->translate('select')),
                                                        'plain'=>ucfirst($this->translatorRepository->translate('plain')),
                                                        'submit'=>ucfirst($this->translatorRepository->translate('save')),
                                                        'statistics'=>ucfirst($this->translatorRepository->translate('statistics')),
                                                        'write'=>ucfirst($this->translatorRepository->translate('output'))],
                                            'source' => $this->source]);
        $this->template->links =  json_encode(['keyword' => $this->link('keyword'),'submit' => $this->link('submit'),'write' => $this->link('write')]);
        $this->template->js = $this->getPresenter()->template->basePath . '/' . $this->jsDir;
        $this->template->setFile(__DIR__ . '/../templates/content.latte');
        $this->template->render();
    }

    /** @return IContentFormFactory */
    public function setRow(IRow $row) {
        $this->row = $row;
        return $this;
    }

    /** @return IContentFormFactory */
    public function setSource(array $source) {
        $this->source = $source;
        return $this;
    }

    /** @return array */
    private function wildcard($option, array $wildcards) {
        foreach (explode(' ', trim(preg_replace('/\s+|\./', ' ', $option))) as $wildcard) {
            $row = $this->keywordsRepository->getKeyword($wildcard);
            if ($row instanceof ActiveRow && !in_array($wildcard, $wildcards) && !empty($wildcard)) {
                foreach(json_decode($row->content) as $content) {
                    $wildcards[$wildcard][] = '%' . $content . '%';
                }
            }
        }
        return $wildcards;
    }

}

interface IContentFormFactory {

    /** @return ContentForm */
    function create();
}

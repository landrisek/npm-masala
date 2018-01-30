<?php

namespace Masala;

/** @author Lubomir Andrisek */
final class HelpRepository extends BaseRepository implements IHelp {

    /** @return array */
    public function getHelp($controller, $action, $parameters) {
        if (null == $help = $this->database->table($this->source)
                ->select('*')
                ->where('source IN', [$controller, $controller . ':' . $action, $controller . ':' . $action . ':' . $parameters])
                ->fetch()) {
            return [];
        }
        return (array) json_decode($help->json);
    }

}

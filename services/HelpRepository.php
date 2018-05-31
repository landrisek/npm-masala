<?php

namespace Masala;

/** @author Lubomir Andrisek */
final class HelpRepository extends BaseRepository implements IHelp {

    public function getHelp(string $controller, string $action, string $parameters): array {
        if (null == $help = $this->database->table($this->source)
                ->select('*')
                ->where('source IN', [$controller, $controller . ':' . $action, $controller . ':' . $action . ':' . $parameters])
                ->limit(1)
                ->fetch()) {
            return [];
        }
        return (array) json_decode($help->json);
    }

}

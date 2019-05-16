<?php

namespace Masala;

final class PostMock {

    public function setPost(array $post): void {
        file_put_contents('php://memory', 'PHP');
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', 'Masala\StreamMock');
        file_put_contents('php://input', json_encode($post));
    }

}

final class StreamMock {

    /** @var int */
    protected $index = 0;

    /** @var int */
    protected $length = null;

    /** @var string */
    protected $data = 'hello world';

    function __construct(){
        if(file_exists($this->buffer_filename())){
            $this->data = file_get_contents($this->buffer_filename());
        }else{
            $this->data = '';
        }
        $this->index = 0;
        $this->length = strlen($this->data);
    }

    protected function buffer_filename(){
        return sys_get_temp_dir().'\php_input.txt';
    }

    function stream_open($path, $mode, $options, &$opened_path){
        return true;
    }

    function stream_close(){
    }

    function stream_stat(){
        return array();
    }

    function stream_flush(){
        return true;
    }

    function stream_read($count){
        if(is_null($this->length) === TRUE){
            $this->length = strlen($this->data);
        }
        $length = min($count, $this->length - $this->index);
        $data = substr($this->data, $this->index);
        $this->index = $this->index + $length;
        return $data;
    }

    function stream_eof(){
        return ($this->index >= $this->length ? TRUE : FALSE);
    }

    function stream_write($data){
        return file_put_contents($this->buffer_filename(), $data);
    }

    function unlink(){
        if(file_exists($this->buffer_filename())){
            unlink($this->buffer_filename());
        }
        $this->data = '';
        $this->index = 0;
        $this->length = 0;
    }
}
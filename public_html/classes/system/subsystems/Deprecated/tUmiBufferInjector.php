<?php
 trait tUmiBufferInjector {private $v7f2db423a49b305459147332fb01cf87;public function setBuffer(iOutputBuffer $v7f2db423a49b305459147332fb01cf87) {$this->buffer = $v7f2db423a49b305459147332fb01cf87;}public function getBuffer() {if (!$this->buffer instanceof iOutputBuffer) {throw new RequiredPropertyHasNoValueException('You should set iOutputBuffer first');}return $this->buffer;}}?>

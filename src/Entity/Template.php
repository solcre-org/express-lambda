<?php

namespace SolcreExpressLambda\Entity;


use SolcreExpressLambda\Exception\Templates\PathNotFoundException;

class Template
{
    protected string $name;
    protected $path;
    protected array $parsedDefinition;

    /**
     * Template constructor.
     *
     */
    public function __construct()
    {
        $this->parsedDefinition = [];
    }


    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        if ($path === null) {
            throw new PathNotFoundException('Path can not be null.');
        }
        $absPath = realpath($path);
        if (!$absPath) {
            throw new PathNotFoundException('Path: ' . $path . ' does not exist.');
        }
        $this->path = $absPath;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the template
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getParsedDefinition(): array
    {
        return $this->parsedDefinition;
    }

    /**
     * @param mixed $parsedDefinition
     */
    public function setParsedDefinition($parsedDefinition): void
    {
        $this->parsedDefinition = $parsedDefinition;
    }

}

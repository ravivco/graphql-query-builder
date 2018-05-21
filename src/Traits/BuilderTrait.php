<?php
/**
 * Created by graphql-query-builder.
 * User: Ruslan Evchev
 * Date: 25.10.16
 * Email: aion.planet.com@gmail.com
 */

namespace QueryBuilder\Traits;

use QueryBuilder\Interfaces\BuilderInterface;
use QueryBuilder\Util;

trait BuilderTrait
{

    protected $name = null;

    protected $arguments;

    protected $body;

    protected $meta;

    protected $argKeys = [];

    protected $bodyPrototype = [];

    protected $enums;

    protected $values = [];

    /**
     * @param string $name
     *
     * @return BuilderInterface|BuilderTrait $this
     */
    public function name(string $name)
    {
        if ($this->name === null) {
            $this->name = $name;
        }

        return $this;
    }

    public function resetName()
    {
        $this->name = null;
    }

    /**
     * @param array $arguments
     *
     * @return BuilderInterface|BuilderTrait $this
     */
    public function arguments(array $arguments)
    {
        if (empty($this->arguments)) {
            $this->processArgumentsNames($arguments);

            $this->arguments = $this->replacePlaceholders(
                sprintf(
                    '(%s)',
                    substr(json_encode($arguments, JSON_UNESCAPED_SLASHES),
                    1,
                    strlen(json_encode($arguments, JSON_UNESCAPED_SLASHES)) - 2))
            );
        }

        $this->processEnums();
        $this->processOrderBy();

        return $this;
    }

    public function resetArguments()
    {
        $this->arguments = null;
    }

    protected function processEnums()
    {
        foreach ($this->values as $value) {
            if(strpos($value, 'ENUM_') !== false) {
                $this->arguments = str_replace(sprintf('"%s"', $value), $value, $this->arguments);
            }
        }

        $this->arguments = str_replace('ENUM_', '', $this->arguments);
    }


    protected function processOrderBy()
    {
        foreach ($this->values as $value) {
            if(strpos($value, 'ORDER_') !== false) {
                $this->arguments = str_replace(sprintf('"%s"', $value), $value, $this->arguments);
            }
        }

        $this->arguments = str_replace('ORDER_', '', $this->arguments);
    }

    /**
     * @param array $body
     *
     * @return BuilderInterface|BuilderTrait $this
     */
    public function body(array $body)
    {
        if (empty($this->body)) {
            $this->bodyPrototype = $body;
            $bodyString          = Util::PARSER_L_BRACE . Util::PARSER_EOL;

            $this->processBody($body, $bodyString);
            $this->body = $bodyString . Util::PARSER_R_BRACE . Util::PARSER_EOL;
        }

        return $this;
    }

    public function resetBody()
    {
        $this->body = null;
    }

    /**
     * @return BuilderInterface|BuilderTrait $this
     *
     */
    public function meta()
    {
        $this->meta = $this->getMetaName() . $this->getMetaArguments() . $this->getMetaBody();

        return $this;
    }

    /**
     * @return string
     */
    private function getMetaName()
    {
        return $this->name.'Connection';
    }

    /**
     * @return mixed
     */
    private function getMetaArguments()
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    private function getMetaBody()
    {
        return Util::PARSER_L_BRACE . $this->processMetaCount() . $this->processMetaPageInfo() . Util::PARSER_R_BRACE;
    }

    private function processMetaCount()
    {
        $meta_body_str = 'aggregate' . Util::PARSER_L_BRACE;
        $meta_body_str .= 'count' . Util::PARSER_R_BRACE;
        $meta_body_str .= Util::PARSER_EOL;

        return $meta_body_str;
    }


    private function processMetaPageInfo()
    {
        $meta_body_str = 'pageInfo' . Util::PARSER_L_BRACE;
        $meta_body_str .= 'hasNextPage' . Util::PARSER_EOL;
        $meta_body_str .= 'hasPreviousPage' . Util::PARSER_EOL;
        $meta_body_str .= 'startCursor' . Util::PARSER_EOL;
        $meta_body_str .= 'endCursor' . Util::PARSER_EOL;
        $meta_body_str .= Util::PARSER_R_BRACE;

        return $meta_body_str;
    }

    /**
     *
     */
    public function resetMeta()
    {
        $this->meta = null;
    }

    protected function processArgumentsNames(array $arguments)
    {
        foreach ($arguments as $argumentName => $argumentValue) {
            if (is_string($argumentName) && !is_numeric($argumentName)) {
                $this->argKeys[$argumentName] = $argumentName;
            }

            if (is_array($argumentValue)) {
                $this->processArgumentsNames($argumentValue);
            } else {
                $this->values[] = $argumentValue;
            }
        }
    }

    protected function replacePlaceholders($argsString)
    {
        foreach ($this->argKeys as $argKey) {
            $argsString = str_replace(
                sprintf('"%s":', $argKey), sprintf('%s:', $argKey), $argsString
            );
        }

        return $argsString;
    }

    protected function processBody($elements, &$bodyString)
    {
        foreach ($elements as $key => $element) {
            if (is_int($key)) {
                $bodyString .= $element . Util::PARSER_EOL;
            } elseif (is_string($key) && is_array($element)) {
                $bodyString .= $key . Util::PARSER_L_BRACE . Util::PARSER_EOL;
                $this->processBody($element, $bodyString);
                $bodyString .= Util::PARSER_R_BRACE . Util::PARSER_EOL;
            }
        }
    }
}
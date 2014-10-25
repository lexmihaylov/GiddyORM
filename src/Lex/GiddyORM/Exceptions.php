<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RelationshipException
 *
 * @author alexander
 */
namespace Lex\GiddyORM;

class SQLException extends Exception {}

class RelationshipException extends Exception {}

class UndefinedRelationshipException extends Exception {}

class ValidationException extends Exception {}

class ModelFinderException extends Exception {}

class UnknownAdapterException extends Exception {}

?>

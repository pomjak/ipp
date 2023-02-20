<?php

    ini_set('display_errors', 'std_err'); 

    define("SUCCESS" , 0);
    define("ERR_PARAM" , 10);
    define("ERR_OUTPUT_FILE" , 11);
    define("ERR_BAD_HEADER" , 21);
    define("ERR_OPCODE" , 22);
    define("ERR_SYNTAX", 23);
    define("ERR_INTERNAL", 99);

    enum Args
    {
        case type;
        case symb;
        case var;
        case label;
    }

    function write_instr($xml,$idx,$opcode,$head)
    {
        if(!$head) exit(ERR_BAD_HEADER);
        $xml->startElement('instruction');
        $xml->writeAttribute('order', $idx);
        $xml->writeAttribute('opcode', $opcode);
    }

    function write_op($xml,$num,$type,$value)
    {
        $xml->startElement('arg'.$num);
        $xml->writeAttribute('type', $type);
        $xml->text($value);
        $xml->endElement();
    }

    function strip_comment($line)
    {
        return ( (strstr($line,'#',true) ) ? trim( (strstr($line, '#', true))) : trim($line) );
    }

    if($argc > 1)
    {
        if($argv[1] == '--help' && $argc == 2)
        {
            echo("Skript typu filtr (parse.php v jazyce PHP 8.1) nacte ze standardniho vstupu zdrojovy kod v IPP-code23,\n");
            echo("zkontroluje lexikalni a syntaktickou spravnost kodu a vypise na standardni výstup XML reprezentaci programu.\n\n"); 
            echo("Tento skript pracuje s temito parametry:\n\t--help \t vypise tuto napovedu\n");
            exit(SUCCESS);
        }
        else exit(ERR_PARAM);
    }

    $xml_buffer = new XMLWriter();

    if(!$xml_buffer->openMemory())
        exit(ERR_INTERNAL);

    if(!$xml_buffer->startDocument('1.0', 'utf-8'))
    {
        $xml_buffer->flush();
        exit(ERR_INTERNAL);
    }

    $xml_buffer->setIndent(true);
    $xml_buffer->setIndentString("    ");

    $xml_buffer->startElement('program');
    $xml_buffer->writeAttribute('language', 'IPPcode23');

    $header_found = false;
    $idx = 0;

    while($line = fgets(STDIN))
    {
        $line = strip_comment($line);

        $tokens = explode(' ',$line,);

        switch($tokens[0] = strtoupper($tokens[0]))
        {
            case ".IPPCODE23":
                if (!$header_found)
                    $header_found = true;
                else 
                    exit (ERR_OPCODE);
                break;
            ## no op
            case "CREATEFRAME":
            case "PUSHFRAME":
            case "POPFRAME":
            case "BREAK":
            case "RETURN":
                write_instr($xml_buffer,++$idx,$tokens[0],$header_found);
                $xml_buffer->endElement();

                break;

            ## label
            case "CALL":
            case "LABEL":
            case "JUMP":
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                write_op($xml_buffer,1,'label',$tokens[1]);
                $xml_buffer->endElement();

                break;

            ##var
            case "DEFVAR":
            case "POPS":
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                write_op($xml_buffer, 1, 'var', $tokens[1]);
                $xml_buffer->endElement();

                break;

            ## symb
            case "PUSHS":
            case "WRITE":
            case "EXIT":
            case "DPRINT":
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                $symb = explode('@', $tokens[1],);
                write_op($xml_buffer, 1, $symb[0], $symb[1]);
                $xml_buffer->endElement();

                break;

            ## var symb
            case "MOVE":
            case "INT2CHAR":
            case "STRLEN":
            case "TYPE":
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                write_op($xml_buffer, 1, 'var', $tokens[1]);
                $symb = explode('@', $tokens[2],);
                write_op($xml_buffer, 2, $symb[0], $symb[1]);      
                $xml_buffer->endElement();

                break;

            ## var symb symb
            case "ADD":
            case "SUB":
            case "MUL":
            case "IDIV":
            case "LT":
            case "GT":
            case "EQ":
            case "AND":
            case "OR":
            case "NOT":
            case "STRI2INT":
            case "CONCAT":
            case "GETCHAR":
            case "SETCHAR":
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                write_op($xml_buffer, 1, 'var', $tokens[1]);
                write_op($xml_buffer, 2, 'symb', $tokens[2]);   
                write_op($xml_buffer, 3, 'symb', $tokens[3]); 
                $xml_buffer->endElement();

                break;

            ## var type
            case "READ":
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                write_op($xml_buffer, 1, 'var', $tokens[1]);
                write_op($xml_buffer, 2, 'type', $tokens[2]);
                $xml_buffer->endElement();

                break;

            ## label symb
            case "JMPIFEQ":
            case "JMPIFNEQ":
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                write_op($xml_buffer, 1, 'label', $tokens[1]);
                write_op($xml_buffer, 2, 'symb', $tokens[2]);
                $xml_buffer->endElement();

                break;

            default:
                break;
        }
    }

    if(!$header_found) exit(ERR_BAD_HEADER);

    $xml_buffer->endElement();
    $xml_buffer->endDocument();
    
    echo($xml_buffer->outputMemory(true));
    $xml_buffer->flush();
    
    exit(SUCCESS);
?>
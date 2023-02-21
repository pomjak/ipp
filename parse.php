<?php

    ini_set('display_errors', 'std_err'); 

    define("SUCCESS" , 0);
    define("ERR_PARAM" , 10);
    define("ERR_OUTPUT_FILE" , 11);
    define("ERR_BAD_HEADER" , 21);
    define("ERR_OPCODE" , 22);
    define("ERR_SYNTAX", 23);
    define("ERR_INTERNAL", 99);

    function err_msg($msg,$err_code)
    {
        fprintf(STDERR,$msg);
        exit($err_code);
    }

    function write_instr($xml,$idx,$opcode,$head)
    {
        if(!$head) err_msg("err: header: bad header", ERR_BAD_HEADER);
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
        if(strstr($line, '#', true))
        {
            $line = strstr($line, '#', true);
            $line = preg_replace('/\s\s+/', ' ', $line);
            $line = trim($line);
        }
        else
        {
            $line = preg_replace('/\s\s+/', ' ', $line);
            $line = trim($line);
        }
        return $line;
    }

    function label_check($token,$xml)
    {
        if( preg_match("/^[a-zA-Z_\-\$&%\*!\?][\w\-\$&%\*!\?]*$/",$token) )
            write_op($xml, 1, 'label', $token);
        else 
            err_msg("err: label_check: $token bad syntax",ERR_SYNTAX);
    }
    
    function type_check($token,$xml)
    {
        if( preg_match("/^(nil|bool|int)$/",$token) )
            write_op($xml, 2, 'type', $token);
        else
            err_msg("err: type_check: $token bad syntax", ERR_SYNTAX);
    }

    function var_check($token,$xml,$order)
    {
        if (preg_match("/^(GF|LF|TF)@[a-zA-Z_\-\$&%\*!\?][\w\-\$&%\*!\?]*$/", $token))
            write_op($xml, $order, 'var', $token);
        else
            err_msg("err: var_check: $token bad syntax", ERR_SYNTAX);
    }

    function const_check($token,$xml,$order)
    {
        $const_sub = explode('@',$token);
        if( preg_match( "/^(nil|int|string|bool)$/" , $const_sub[0]) )
        {
            switch($const_sub[0])
            {
                case "int":
                    if(preg_match("/^[+-]?[0-9]+$/",$const_sub[1]))
                        write_op($xml, $order, $const_sub[0], $const_sub[1]);
                    else err_msg("err: const_check: $const_sub[1] bad syntax", ERR_SYNTAX);
                    break;

                case "nil":
                    if (preg_match("/^nil$/", $const_sub[1]))
                        write_op($xml, $order, $const_sub[0], $const_sub[1]);
                    else err_msg("err: const_check: $const_sub[1] bad syntax", ERR_SYNTAX);
                    break;

                case "string":
                    if (preg_match("/^([^\\\]|[\\\][\d]{3})*$/", $const_sub[1]))
                        write_op($xml, $order, $const_sub[0], $const_sub[1]);
                    else err_msg("err: const_check: $const_sub[1] bad syntax", ERR_SYNTAX);
                    break;

                case "bool":
                    if (preg_match("/^(true|false)$/", $const_sub[1]))
                        write_op($xml, $order, $const_sub[0], $const_sub[1]);
                    else err_msg("err: const_check: $const_sub[1] bad syntax", ERR_SYNTAX);
                break;
            }
        }
        else err_msg("err: const_check: $token bad syntax", ERR_SYNTAX);
    }

    function symb_check($token,$xml,$order)
    {
        $symb = explode('@',$token);
        if (preg_match("/^(GF|LF|TF)$/", $symb[0]))
            var_check($token,$xml,$order);
        else
            const_check($token, $xml,$order);
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
    else err_msg("err:param_check :bad params", ERR_PARAM);
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
        if($line[0] == '#') continue;
        $line = strip_comment($line);

        $tokens = explode(' ',$line,);

        switch($tokens[0] = strtoupper($tokens[0]))
        {
            case ".IPPCODE23":
                if (!$header_found)
                    $header_found = true;
                else
                    err_msg("err: header : double header", ERR_OPCODE);
                break;
            ## no op
            case "CREATEFRAME":
            case "PUSHFRAME":
            case "POPFRAME":
            case "BREAK":
            case "RETURN":
                if( count($tokens) != 1 ) err_msg("bad num of op : count($tokens)",ERR_SYNTAX);
                write_instr($xml_buffer,++$idx,$tokens[0],$header_found);
                $xml_buffer->endElement();

                break;

            ## label
            case "CALL":
            case "LABEL":
            case "JUMP":
                if (count($tokens) != 2) err_msg("bad num of op : count($tokens)", ERR_SYNTAX);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                label_check($tokens[1],$xml_buffer);
                $xml_buffer->endElement();

                break;

            ##var
            case "DEFVAR":
            case "POPS":
                if (count($tokens) != 2) err_msg("bad num of op : count($tokens)", ERR_SYNTAX);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                var_check($tokens[1], $xml_buffer,1);
                $xml_buffer->endElement();

                break;

            ## symb
            case "PUSHS":
            case "WRITE":
            case "EXIT":
            case "DPRINT":
                if (count($tokens) != 2) err_msg("bad num of op : count($tokens)", ERR_SYNTAX);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                symb_check($tokens[1],$xml_buffer,1);
                $xml_buffer->endElement();

                break;

            ## var symb
            case "MOVE":
            case "INT2CHAR":
            case "STRLEN":
            case "TYPE":
            case "NOT":
                if (count($tokens) != 3) err_msg("bad num of op : count($tokens)", ERR_SYNTAX);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                var_check($tokens[1], $xml_buffer,1);
                symb_check($tokens[2], $xml_buffer,2);
                $xml_buffer->endElement();

                break;

            ## var symb symb
            if (count($tokens) != 4) err_msg("bad num of op : count($tokens)", ERR_SYNTAX);
            case "ADD":
            case "SUB":
            case "MUL":
            case "IDIV":
            case "LT":
            case "GT":
            case "EQ":
            case "AND":
            case "OR":
            case "STRI2INT":
            case "CONCAT":
            case "GETCHAR":
            case "SETCHAR":
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                var_check($tokens[1], $xml_buffer,1);
                symb_check($tokens[2], $xml_buffer,2);
                symb_check($tokens[3], $xml_buffer,3);
                $xml_buffer->endElement();

                break;

            ## var type
            case "READ":
                if (count($tokens) != 3) err_msg("bad num of op : count($tokens)", ERR_SYNTAX);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                var_check($tokens[1], $xml_buffer,1);
                type_check($tokens[2],$xml_buffer);
                $xml_buffer->endElement();

                break;

            ## label symb symb
            case "JUMPIFEQ":
            case "JUMPIFNEQ":
                if (count($tokens) != 4 ) err_msg("bad num of op : count($tokens)", ERR_SYNTAX);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                label_check($tokens[1], $xml_buffer);
                symb_check($tokens[2], $xml_buffer,2);
                symb_check($tokens[3], $xml_buffer, 3);
                $xml_buffer->endElement();

                break;

            default:
                err_msg("err: switch :unrecognized command $tokens[0]", ERR_OPCODE);
                break;
        }
    }

    if(!$header_found) err_msg("err: header :missing header", ERR_BAD_HEADER);

    $xml_buffer->endElement();
    $xml_buffer->endDocument();
    
    echo($xml_buffer->outputMemory(true));
    $xml_buffer->flush();
    
    exit(SUCCESS);
?>
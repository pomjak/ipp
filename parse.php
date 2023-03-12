<?php

    ini_set('display_errors', 'std_err'); 

    define("SUCCESS" , 0);
    define("ERR_PARAM" , 10);
    define("ERR_OUTPUT_FILE" , 11);
    define("ERR_BAD_HEADER" , 21);
    define("ERR_OPCODE" , 22);
    define("ERR_SYNTAX", 23);
    define("ERR_INTERNAL", 99);

    
    /**
     * prints err message and exits with err code 
     * @param mixed $msg message that will be diplayed
     * @param mixed $err_code errot code that will be returned
     * 
     */
    function err_msg($msg,$err_code)
    {
        
        fprintf(STDERR,"$msg\n");
        exit($err_code);
    }

    /**
     * stores instruction opcode with order in xml format to buffer
     * @param mixed $xml buffer that stores xml data
     * @param mixed $idx index of instruction
     * @param mixed $opcode name of opcode
     * @param mixed $head confirmation if header was found
     * 
     */
    function write_instr($xml,$idx,$opcode,$head)
    {
        if(!$head) err_msg("err: header: bad header", ERR_BAD_HEADER);
        $xml->startElement('instruction');
        $xml->writeAttribute('order', $idx);
        $xml->writeAttribute('opcode', $opcode);
    }


    /**
     * stores argument of instruction with index,type and value in xml format to buffer
     * @param mixed $xml buffer that stores xml data
     * @param mixed $num index of argument
     * @param mixed $type type of argument
     * @param mixed $value value of argument
     * 
     */
    function write_op($xml,$num,$type,$value)
    {
        $xml->startElement('arg'.$num);
        $xml->writeAttribute('type', $type);
        $xml->text($value);
        $xml->endElement();
    }

    /**
     * discards comments,white characters and the newline character
     * @param mixed $line of input to be processed
     * @return mixed $line stripped line
     */
    function strip($line)
    {
        if(strstr($line, '#', true))//if comment found, throw away it
            $line = strstr($line, '#', true);

        $line = preg_replace('/\s\s+/', ' ', $line);//replaces every whitespace with space
        $line = trim($line);//discards new line character
        
        return $line;
    }

    /**
     * checks the right number of tokens
     * @param mixed $tokens tokens to be counted
     * @param mixed $num_of_op expected token count
     * 
     */
    function count_check($tokens,$num_of_op)
    {
        if (count($tokens) != ($num_of_op+1) ) // +1 for instruction
            err_msg("bad num of op : count($tokens)", ERR_SYNTAX);
    }

    /**
     * checks the correctness of label by regex
     * @param mixed $token to be checked
     * @param mixed $xml buffer to be stored into to
     * 
     */
    function label_check($token,$xml)
    {
        if( preg_match("/^[a-zA-Z_\-\$&%\*!\?][\w\-\$&%\*!\?]*$/",$token) )
            write_op($xml, 1, 'label', $token);
        else 
            err_msg("err: label_check: $token bad syntax",ERR_SYNTAX);
    }

    /**
     * checks the correctness of type by regex
     * @param mixed $token to be checked
     * @param mixed $xml buffer to be stored into to
     * 
     */
    function type_check($token,$xml)
    {
        if( preg_match("/^(nil|bool|int|string)$/",$token) )
            write_op($xml, 2, 'type', $token);
        else
            err_msg("err: type_check: $token bad syntax", ERR_SYNTAX);
    }

    /**
     * checks the correctness of variable by regex
     * @param mixed $token to be checked
     * @param mixed $xml buffer to be stored into to
     * @param mixed $order position of arg
     * 
     */
    function var_check($token,$xml,$order)
    {
        if (preg_match("/^(GF|LF|TF)@[a-zA-Z_\-\$&%\*!\?][\w\-\$&%\*!\?]*$/", $token))
            write_op($xml, $order, 'var', $token);
        else
            err_msg("err: var_check: $token bad syntax", ERR_SYNTAX);
    }

    /**
     * checks the correctness of constatnt by regex
     * @param mixed $token to be checked
     * @param mixed $xml buffer to be stored into to
     * @param mixed $order position of arg
     * 
     */
    function const_check($token,$xml,$order)
    {
        $const_sub = explode('@',$token);//const_sub[0]...type of const, const_sub[1]...value of const
        if( preg_match( "/^(nil|int|string|bool)$/" , $const_sub[0]) )//regex for right type
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

    /**
     * decides if symbol is represented as variable or constant and checks it
     * @param mixed $token to be checked
     * @param mixed $xml buffer to be stored into to
     * @param mixed $order position of arg
     * 
     */
    function symb_check($token,$xml,$order)
    {
        $symb = explode('@',$token);
        if (preg_match("/^(GF|LF|TF)$/", $symb[0]))
            var_check($token,$xml,$order);
        else
            const_check($token, $xml,$order);
    }

    /**
     * 
     * 
     * MAIN STRUCTURE OF PROGRAM
     * 
     * 
     */

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

    $xml_buffer = new XMLWriter();//creating buffer for xml 

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

    while($line = fgets(STDIN))//storing stdin 
    {
        if($line[0] == '#') continue;//if $line start with # then it could be discarded 
        $line = strip($line);

        if(!$header_found)
        {
            if(strtoupper($line) == ".IPPCODE23")
            {
                $header_found = true;
                continue;
            }
            elseif($line == "") continue;//if blank line, skip
            else err_msg("err: header : missing or bad header", ERR_BAD_HEADER);
        }

        $tokens = explode(' ',$line,);//tokenization of line by space

        switch($tokens[0] = strtoupper($tokens[0]))//switch for instructions
        {
            case ".IPPCODE23"://treating for double header
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
                count_check($tokens,0);
                write_instr($xml_buffer,++$idx,$tokens[0],$header_found);
                break;

            ## label
            case "CALL":
            case "LABEL":
            case "JUMP":
                count_check($tokens, 1);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                label_check($tokens[1],$xml_buffer);
                break;

            ##var
            case "DEFVAR":
            case "POPS":

                count_check($tokens, 1);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                var_check($tokens[1], $xml_buffer,1);
                break;

            ## symb
            case "PUSHS":
            case "WRITE":
            case "EXIT":
            case "DPRINT":
                count_check($tokens, 1);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                symb_check($tokens[1],$xml_buffer,1);
                break;

            ## var symb
            case "MOVE":
            case "INT2CHAR":
            case "STRLEN":
            case "TYPE":
            case "NOT":
                count_check($tokens, 2);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                var_check($tokens[1], $xml_buffer,1);
                symb_check($tokens[2], $xml_buffer,2);
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
            case "STRI2INT":
            case "CONCAT":
            case "GETCHAR":
            case "SETCHAR":
                count_check($tokens, 3);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                var_check($tokens[1], $xml_buffer,1);
                symb_check($tokens[2], $xml_buffer,2);
                symb_check($tokens[3], $xml_buffer,3);

                break;

            ## var type
            case "READ":
                count_check($tokens, 2);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                var_check($tokens[1], $xml_buffer,1);
                type_check($tokens[2],$xml_buffer);
                break;

            ## label symb symb
            case "JUMPIFEQ":
            case "JUMPIFNEQ":
                count_check($tokens, 3);
                write_instr($xml_buffer, ++$idx, $tokens[0],$header_found);
                label_check($tokens[1], $xml_buffer);
                symb_check($tokens[2], $xml_buffer,2);
                symb_check($tokens[3], $xml_buffer, 3);
                break;

            case "":
                continue 2;//php curiosity, switch is recognized as loop, 2 for reaching while 

            default:
                err_msg("err: switch :unrecognized command $tokens[0]", ERR_OPCODE);
                break;
        }
    
    $xml_buffer->endElement();
    
    }

    if(!$header_found) err_msg("err: header :missing header", ERR_BAD_HEADER);//checking header in program without any instructions

    $xml_buffer->endElement();
    $xml_buffer->endDocument();
    
    echo($xml_buffer->outputMemory(true));//printing whole buffer to stdout 
    $xml_buffer->flush();//cleaning
    
    exit(SUCCESS);
?>
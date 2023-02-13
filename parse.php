<?php

    ini_set('display_errors', 'std_err'); 

    define("SUCCESS" , 0);
    define("ERR_PARAM" , 10);
    define("ERR_OUTPUT_FILE" , 11);
    define("ERR_BAD_HEADER" , 21);
    define("ERR_OPCODE" , 22);
    define("ERR_SYNTAX", 23);
    define("ERR_INTERNAL", 99);

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

    if(!$xml_buffer->setIndent(true))
    {
        $xml_buffer->flush();
        exit(ERR_INTERNAL);
    }

    if(!$xml_buffer->startElement('program'))
    {
        $xml_buffer->flush();
        exit(ERR_INTERNAL);
    }

    if(!$xml_buffer->writeAttribute('language', 'IPPcode23')) 
    {
        $xml_buffer->flush();
        exit(ERR_INTERNAL);
    }

    $header_found = false;

    while($line = fgets(STDIN))
    {
        if(!$header_found)
        {
            if(strtolower($line) == ".ippcode23")
            {
                $header_found = true;
            }
        }

        $tokens = explode(' ',trim($line,"\n"),);
        
        switch(strtoupper($tokens[0]))
        {
            ## no op
            case "CREATEFRAME":
            case "PUSHFRAME":
            case "POPFRAME":
            case "BREAK":
            case "RETURN":
                break;

            ## label
            case "CALL":
            case "LABEL":
            case "JUMP":
                break;

            ##var
            case "DEFVAR":
            case "POPS":
                break;

            ## symb
            case "PUSHS":
            case "WRITE":
            case "EXIT":
            case "DPRINT":
                break;

            ## var symb
            case "MOVE":
            case "INT2CHAR":
            case "STRLEN":
            case "TYPE":
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
                break;

            ## var type
            case "READ":
            break;

            ## label symb
            case "JMPIFEQ":
            case "JMPIFNEQ":
            break;

            default:
                ##error TODO
                break;
        }
    }


    $xml_buffer->endElement();
    $xml_buffer->endDocument();

    echo($xml_buffer->outputMemory(true));

    exit(SUCCESS);
?>
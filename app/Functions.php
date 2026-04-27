<?php

class Functions {

    /*******************************************************************************************************************/
	/*                                                                                                                 */
	/*                                                 Instancias                                                      */
	/*                                                                                                                 */
	/*******************************************************************************************************************/
	/************************************************************************************************************/
	//Definiciones
	const optionsMesLargo  = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
	const optionsMesCorto  = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
	const optionsDiaSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    /************************************************************************************************************/
	/**
	 * Segmenta una cadena de caracteres delimitada por comas en un arreglo de elementos.
	 *
	 * Utiliza una expresión regular para dividir la cadena, eliminando espacios en blanco
	 * alrededor de las comas y omitiendo fragmentos que resulten vacíos.
	 *
	 * @param string $Data Cadena de texto con elementos separados por comas.
	 *
	 * @return array Arreglo que contiene los elementos individuales extraídos.
	 *
	 * @example
	 * ```php
	 * $CommonData->parseDataCommas('uno,dos,tres');
	 * ```
	 *
	 */
	public static function parseDataCommas($Data): array {

		/**********************  Retorno datos  **********************/
		// Divide la cadena basándose en comas, permitiendo espacios opcionales (\s*)
		return preg_split('/\s*,\s*/', $Data, -1, PREG_SPLIT_NO_EMPTY);
	}

    /************************************************************************************************************/
	/**
	 * Segmenta una cadena de caracteres delimitada por guiones medios en un arreglo.
	 *
	 * Utiliza una expresión regular para dividir la cadena utilizando el carácter '-'
	 * como delimitador, eliminando espacios en blanco adyacentes y descartando
	 * resultados vacíos.
	 *
	 * @param string $Data Cadena de texto que contiene los elementos separados por guiones.
	 *
	 * @return array Arreglo con los elementos individuales extraídos.
	 *
	 * @example
	 * ```php
	 * $CommonData->parseDataSeparator('uno-dos-tres');
	 * ```
	 *
	 */
	public static function parseDataSeparator($Data): array {

		/**********************  Retorno datos  **********************/
		// Divide la cadena basándose en guiones, permitiendo espacios opcionales (\s*)
		return preg_split('/\s*-\s*/', $Data, -1, PREG_SPLIT_NO_EMPTY);
	}

	/************************************************************************************************************/
	/**
	 * Divide una cadena de texto utilizando operadores de comparación como delimitadores.
	 *
	 * Emplea una expresión regular para identificar símbolos lógicos (!=, <=, >=, =, <, >)
	 * y separar la cadena en sus componentes, ignorando espacios en blanco alrededor
	 * de dichos símbolos.
	 *
	 * @param string $Data Cadena con datos y operadores de comparación.
	 *
	 * @return array Arreglo con los fragmentos de texto resultantes de la división.
	 *
	 * @example
	 * ```php
	 * $CommonData->parseDataSymbol('uno=dos!=tres');
	 * ```
	 *
	 */
	public static function parseDataSymbol($Data): array {

		/********************** Si todo esta ok **********************/
		// Ejecuta la división mediante un grupo de no captura para los operadores lógicos
		$Data = preg_split('/\s*(?:!=|<=|>=|=|<|>)\s*/', $Data, -1, PREG_SPLIT_NO_EMPTY);

		/**********************  Retorno datos  **********************/
		// Retorno del arreglo procesado
		return $Data;
	}

	/************************************************************************************************************/
	/**
     * Valida si una cadena de texto corresponde a un RUT chileno válido.
     * * El proceso incluye la limpieza de puntos, validación de formato mediante expresiones
     * regulares y el cálculo del dígito verificador utilizando el algoritmo del Módulo 11.
     *
     * @param string $Data El RUT a validar (ej: '12.345.678-9' o '12345678-9').
     *
     * @return bool True si el RUT es válido, false en caso contrario.
	 *
	 * @example
	 * ```php
	 * $DataValidations->validarRut('10.569.874-5');
	 * ```
	 *
     */
    public static function validarRut($Data): bool {

        /********************** Validaciones Iniciales **********************/
        if($Data == '' || $Data == '0'){ return false; }

        /********************** Limpieza y Formateo **********************/
        // Elimina puntos para normalizar la cadena
        $rut = str_replace('.', '', $Data);

        // Verifica longitud mínima (un RUT válido tiene al menos 3 caracteres: 1-k)
        if (empty($rut) || strlen($rut) < 3) {
            return false;
        }

        // Separa la parte numérica del guion y dígito verificador
        $parteNumerica = str_replace(substr($rut, -2, 2), '', $rut);

        // Valida que la parte izquierda sean solo dígitos
        if (!preg_match("/^[0-9]*$/", $parteNumerica)) {
            return false;
        }

        $guionYVerificador = substr($rut, -2, 2);

        // El formato debe terminar estrictamente en "-X" donde X es 0-9 o K
        if (strlen($guionYVerificador) != 2 || !preg_match('/(^[-]{1}+[0-9kK]).{0}$/', $guionYVerificador)) {
            return false;
        }

        /********************** Algoritmo Módulo 11 **********************/
        // Prepara la cadena eliminando guiones y puntos para el cálculo
        $rutV   = preg_replace('/[\.\-]/i', '', $rut);
        $dv     = substr($rutV, -1);
        $numero = substr($rutV, 0, strlen($rutV) - 1);

        $i      = 2;
        $suma   = 0;

        // Multiplicación por serie 2,3,4,5,6,7 y suma
        foreach (array_reverse(str_split($numero)) as $v) {
            if ($i == 8) { $i = 2; }
            $suma += $v * $i;
            ++$i;
        }

        // Cálculo del dígito esperado
        $dvr = 11 - ($suma % 11);
        if ($dvr == 11) { $dvr = 0; }
        if ($dvr == 10) { $dvr = 'K'; }

        /********************** Retorno de Datos **********************/
        // Compara el dígito calculado con el ingresado
        return ($dvr == strtoupper($dv));
    }

	/************************************************************************************************************/
	/**
     * Valida si una cadena de texto tiene un formato de correo electrónico válido.
     * * Utiliza el filtro nativo de PHP FILTER_VALIDATE_EMAIL, que cumple con
     * gran parte de los estándares RFC.
     *
     * @param string $Data Correo electrónico a validar.
     *
     * @return bool True si el formato es correcto.
	 *
	 * @example
	 * ```php
	 * $DataValidations->validarEmail('asd@asd.cl'); //Devuelve true
	 * $DataValidations->validarEmail('asd@asd');    //Devuelve false
	 * ```
	 *
     */
    public static function validarEmail($Data): bool {

        /**********************  Validaciones   **********************/
        if($Data == ''){ return false; }

        /********************** Retorno de Datos **********************/
        return (bool) filter_var($Data, FILTER_VALIDATE_EMAIL);
    }

	/************************************************************************************************************/
	/**
     * Valida si el dato ingresado es un valor numérico.
     * * Acepta números enteros, decimales (usando punto o coma) y valores negativos.
     *
     * @param mixed $Data Dato a validar.
     *
     * @return bool True si es un número válido.
	 *
	 * @example
	 * ```php
	 * $DataValidations->validarNumero(25);   //Devuelve true
	 * $DataValidations->validarNumero('25'); //Devuelve false
	 * ```
	 *
     */
    public static function validarNumero($Data): bool {

        /**********************  Validaciones   **********************/
        if($Data === ''){ return false; }

        /********************** Normalización **********************/
        // Reemplaza comas por puntos para que is_numeric reconozca el formato decimal estándar
        $number = str_replace(',', '.', $Data);

        /********************** Retorno de Datos **********************/
        return is_numeric($number);
    }

	/************************************************************************************************************/
	/**
     * Valida si una cadena corresponde al formato de una patente vehicular chilena.
     * * Soporta tanto el formato antiguo (AA-1234) como el formato nuevo (BB-CC-12),
     * validando que no se utilicen vocales en el formato nuevo según la norma.
     *
     * @param string $Data Patente a validar.
     *
     * @return bool True si cumple con el patrón RegEx.
	 *
	 * @example
	 * ```php
	 * $DataValidations->ValidarPatente('AU1825');  //Devuelve true
	 * $DataValidations->ValidarPatente('512369');  //Devuelve false
	 * ```
	 *
     */
    public static function ValidarPatente($Data): bool {

        /**********************  Validaciones   **********************/
        if($Data == ''){ return false; }

        /********************** Limpieza **********************/
        $patente = str_replace("-", "", $Data);

        // RegEx para:
        // 1. Formato Antiguo: 2 letras + 4 números
        // 2. Formato Nuevo: 4 consonantes (sin vocales) + 2 números
        $regex = '/^[a-z]{2}[\.\- ]?[0-9]{2}[\.\- ]?[0-9]{2}|[b-d,f-h,j-l,p,r-t,v-z]{2}[\-\. ]?[b-d,f-h,j-l,p,r-t,v-z]{2}[\.\- ]?[0-9]{2}$/i';

        /********************** Retorno de Datos **********************/
        return (bool) preg_match($regex, $patente);
    }

	/************************************************************************************************************/
	/**
     * Valida si una cadena de texto es una URL con formato válido.
     *
     * @param string $Data URL a validar.
     *
     * @return bool True si es una URL válida (incluyendo protocolo).
	 *
	 * @example
	 * ```php
	 * $DataValidations->validarURL(https://www.google.cl');  //Devuelve true
	 * $DataValidations->validarURL(https://www.  SSS  ');    //Devuelve false
	 * ```
	 *
     */
    public static function validarURL($Data): bool {

        /**********************  Validaciones   **********************/
        if($Data == ''){ return false; }

        /********************** Retorno de Datos **********************/
        return (bool) filter_var($Data, FILTER_VALIDATE_URL);
    }

	/************************************************************************************************************/
	/**
     * Valida si una cadena representa una hora válida en formato H:M o H:M:S.
     * * Permite un rango de horas extendido (hasta 999) útil para cronómetros o
     * sumatoria de tiempos, validando que los minutos y segundos no excedan de 59.
     *
     * @param string $Data Hora a validar (ej: '16:24:00' o '120:30').
     *
     * @return bool True si el formato y los valores son correctos.
	 *
	 * @example
	 * ```php
	 * $DataValidations->validarHora('16:24:00'); //Devuelve true
	 * $DataValidations->validarHora(16);         //Devuelve false
	 * ```
	 *
     */
    public static function validarHora($Data): bool {

        /**********************  Validaciones   **********************/
        // Limpia espacios en blanco al inicio y final
        // (muy común cuando los datos vienen de formularios o BD)
        $Data = trim($Data);

        // Validaciones básicas de entrada
        // - Evita string vacío
        // - Evita fechas "nulas" típicas de BD
        if ($Data === '' || $Data === '00:00:00') {
            return false;
        }

        /********************** Definición de Patrón **********************/
        /**
         * ^ (Inicio)
         * (?:[0-9]{1,3}) -> Horas de 1 a 3 dígitos (0-999)
         * : -> Separador obligatorio
         * (?:[0-5][0-9]) -> Minutos del 00 al 59
         * (?::[0-5][0-9])? -> Segundos del 00 al 59 (opcionales)
         * $ (Fin)
         */
        $patron = '/^(?:[0-9]{1,3}):(?:[0-5][0-9])(?::[0-5][0-9])?$/';

        if (preg_match($patron, $Data)) {
            $partes = explode(':', $Data);
            $horas = (int)$partes[0];

            // Validación de tope máximo definido en lógica de negocio
            return $horas <= 999;
        }

        /********************** Retorno de Datos **********************/
        return false;
    }

	/************************************************************************************************************/
	/**
     * Valida si una cadena corresponde a una fecha real según un formato específico.
     *
     * ✔ Soporta validación estricta usando DateTime
     * ✔ Detecta errores y warnings internos de parsing
     * ✔ Evita fechas inválidas como 2023-02-31
     * ✔ Elimina espacios en blanco que puedan invalidar la comparación
     *
     * @param string $Data   Cadena de fecha a validar
     * @param string $format Formato esperado (por defecto 'Y-m-d')
     *
     * @return bool True si la fecha es válida y coincide exactamente con el formato
	 *
	 * @example
	 * ```php
	 * $DataValidations->validarFecha('1900-01-01');          //Devuelve true
	 * $DataValidations->validarFecha('1900-01-01', 'Y-m-d'); //Devuelve true
     * $DataValidations->validarFecha('a');                   //Devuelve false
	 * ```
	 *
     */
    public static function validarFecha($Data, $format = 'Y-m-d'): bool {

        /**********************  Validaciones   **********************/
        // Limpia espacios en blanco al inicio y final
        // (muy común cuando los datos vienen de formularios o BD)
        $Data = trim($Data);

        // Validaciones básicas de entrada
        // - Evita string vacío
        // - Evita fechas "nulas" típicas de BD
        if ($Data === '' || $Data === '0000-00-00') {
            return false;
        }

        /********************** Si todo esta ok **********************/
        // Se establece zona horaria
        date_default_timezone_set('UTC');
        date_default_timezone_set('America/Santiago');
        // Intenta crear un objeto DateTime a partir del formato dado
        $d = DateTime::createFromFormat('!' . $format, $Data);

        // Si no se pudo crear el objeto, la fecha es inválida
        if (!$d) {
            return false;
        }

        // Obtiene errores y advertencias del último parsing
        // DateTime puede crear objetos incluso con datos incorrectos,
        // por lo que es necesario validar estos errores manualmente
        $errors = DateTime::getLastErrors();

        // Si hay warnings o errores, la fecha no es válida
        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return false;
        }

        /********************** Retorno datos  **********************/
        // Validación final estricta:
        // Compara la fecha formateada con la original
        // Esto evita casos como:
        // '2023-02-31' → se convierte en '2023-03-03'
        return $d->format($format) === $Data;

    }

	/************************************************************************************************************/
	/**
     * Valida si el dato ingresado es un número entero.
     * * A diferencia de is_int(), esta función permite validar números que vienen
     * como strings (común en formularios) siempre que no contengan decimales.
     *
     * @param mixed $Data Dato a validar.
     *
     * @return bool True si es un número entero.
	 *
	 * @example
	 * ```php
	 * $DataValidations->validarEntero(16);   //Devuelve true
	 * $DataValidations->validarEntero('16'); //Devuelve false
	 * ```
	 *
     */
    public static function validarEntero($Data): bool {

        /********************** Validaciones   **********************/
        if($Data === ''){ return false; }

        /********************** Si todo esta ok **********************/
        /********************** Retorno datos  **********************/
        // is_numeric asegura que sea un número, ctype_digit asegura que no tenga decimales ni signos
        return (is_numeric($Data)) ? ctype_digit(strval($Data)) : false;

    }

	/************************************************************************************************************/
	/**
     * Detecta si el usuario está accediendo desde un dispositivo móvil.
     * * Analiza la cadena HTTP_USER_AGENT del navegador en busca de palabras clave
     * comunes de sistemas operativos y navegadores móviles.
     *
     * @return bool True si se detecta un dispositivo móvil o tablet.
	 *
	 * @example
	 * ```php
	 * $DataValidations->validarDispositivoMovil();
	 * ```
	 *
     */
    public static function validarDispositivoMovil(): bool {

        // Obtiene el User Agent del servidor
        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

        /********************** Si todo esta ok **********************/
        // Lista de palabras clave para identificar plataformas móviles
        $movilKeywords = [
            'android', 'iphone', 'ipod', 'ipad', 'blackberry', 'windows phone',
            'opera mini', 'opera mobi', 'mobile', 'silk', 'kindle', 'webos',
            'palm', 'symbian', 'fennec', 'maemo', 'nokia', 'htc', 'samsung',
            'lg', 'motorola', 'tablet', 'playbook'
        ];

        /********************** Retorno datos  **********************/
        foreach ($movilKeywords as $keyword) {
            if (strpos($userAgent, $keyword) !== false) {
                return true;
            }
        }
        return false;

    }

	/************************************************************************************************************/
	/**
     * Valida que una cadena de texto tenga al menos una cantidad mínima de caracteres.
     *
     * @param string $oracion Texto a validar.
     * @param int $largo Cantidad mínima de caracteres requerida.
     *
     * @return bool True si cumple con el largo mínimo.
	 *
	 * @example
	 * ```php
	 * 	$DataValidations->validarLargoMinimo('Lorem ipsum dolor sit amet, consectetur', 10); //Devuelve 'El dato ingresado debe tener no mas de 10 caracteres'
	 * 	$DataValidations->validarLargoMinimo('Lorem', 10); //Devuelve 1
	 * ```
	 *
     */
    public static function validarLargoMinimo($oracion, $largo): bool {

        /********************** Validaciones   **********************/
        // Validaciones básicas de entrada
        // - Evita string vacío
        if ($oracion === '') {
            return false;
        }
        // Asegura que el parámetro de comparación sea un número válido
        if (!self::validarNumero($largo) || !self::validarEntero($largo)){  return false; }

        /********************** Si todo esta ok **********************/
        /********************** Retorno datos  **********************/
        return strlen((string)$oracion) >= $largo;

    }

	/************************************************************************************************************/
	/**
     * Valida que una cadena de texto no exceda una cantidad máxima de caracteres.
     *
     * @param string $oracion Texto a validar.
     * @param int $largo Cantidad máxima de caracteres permitida.
     *
     * @return bool True si el texto es igual o menor al largo indicado.
	 *
	 * @example
	 * ```php
	 * 	$DataValidations->validarLargoMaximo('Lorem', 10); //Devuelve 'El dato ingresado debe tener al menos 10 caracteres'
	 * 	$DataValidations->validarLargoMaximo('Lorem ipsum dolor sit amet, consectetur', 10); //Devuelve 1
	 * ```
	 *
     */
    public static function validarLargoMaximo($oracion, $largo): bool {

        /********************** Validaciones   **********************/
        // Validaciones básicas de entrada
        // - Evita string vacío
        if ($oracion === '') {
            return false;
        }
        if (!self::validarNumero($largo) || !self::validarEntero($largo)){  return false; }

        /********************** Si todo esta ok **********************/
        /********************** Retorno datos  **********************/
        return strlen((string)$oracion) <= $largo;

    }

	/************************************************************************************************************/
	/**
     * Formatea una fecha al estilo: "Mes Día del Año".
     * * @param string|date $Fecha Fecha a formatear.
     *
     * @return string Fecha formateada (ej: "Enero 01 del 2024") o mensaje de error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fechaCompleta('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fechaCompleta('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fechaCompleta('2024-01-01'); //Devuelve enero 01 del 2024
	 * ```
	 *
     */
    public static function fechaCompleta($Fecha): string{

        /********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

        /********************** Si todo esta ok **********************/
        // Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$mes_c = new DateTime($Fecha);
		// Extrae el día del mes con ceros iniciales (01 a 31)
		$dia = $mes_c->format('d');
		// Obtiene el año en formato de cuatro dígitos
        $ano = $mes_c->format('Y');
        // Obtiene el nombre del mes desde el array optionsMesLargo usando el índice numérico del mes (0-11)
        $mes = self::optionsMesLargo[$mes_c->format('m') - 1];

        /********************** Retorno datos  **********************/
        return $mes.' '.$dia.' del '.$ano;

    }

	/************************************************************************************************************/
	/**
     * Formatea una fecha al estilo alternativo: "Día de Mes de Año".
     * * @param string|date $Fecha Fecha a formatear.
     *
     * @return string Fecha formateada (ej: "01 de enero de 2024") o error.
	 *
	 * @example
	 * ```php
	 * $DataDate->fechaCompletaAlt('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fechaCompletaAlt('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fechaCompletaAlt('2024-01-01'); //Devuelve 01 de enero de 2024
	 * ```
	 *
     */
    public static function fechaCompletaAlt($Fecha): string{

        /********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

        /********************** Si todo esta ok **********************/
		// Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$mes_c = new DateTime($Fecha);
		// Extrae el día del mes con ceros iniciales (01 a 31)
		$dia = $mes_c->format('d');
		// Obtiene el año en formato de cuatro dígitos
        $ano = $mes_c->format('Y');
		// Obtiene el nombre del mes desde el array optionsMesLargo usando el índice numérico del mes (0-11)
		$mes = self::optionsMesLargo[$mes_c->format('m') - 1];

        /********************** Retorno datos  **********************/
        return $dia.' de '.$mes.' de '.$ano;

    }

	/************************************************************************************************************/
	/**
	 * Formatea una fecha para obtener el día y el nombre del mes en formato largo.
	 *
	 * @param string $Fecha Fecha en formato válido para procesamiento.
	 *
	 * @return string Retorna el día y mes (ej: "01 Enero") o el mensaje de error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->diaMes('');           //Devuelve Sin fecha ingresada
	 * $DataDate->diaMes('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->diaMes('2024-01-01'); //Devuelve 01 Enero
	 * ```
	 *
	 */
	public static function diaMes($Fecha): string {

		/**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/********************** Si todo esta ok **********************/
		// Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$mes_c = new DateTime($Fecha);
		// Extrae el día del mes con ceros iniciales (01 a 31)
		$dia = $mes_c->format('d');
		// Obtiene el nombre del mes desde el array optionsMesLargo usando el índice numérico del mes (0-11)
		$mes = self::optionsMesLargo[$mes_c->format('m') - 1];

		/**********************  Retorno datos  **********************/
		// Concatena el día y el nombre del mes para el resultado final
		return $dia . ' ' . $mes;
	}

	/************************************************************************************************************/
	/**
	 * Convierte una fecha al formato estándar extendido (día-mes-año de cuatro dígitos).
	 *
	 * @param string $Fecha Fecha a formatear.
	 *
	 * @return DateTime|string Retorna la fecha en formato 'd-m-Y' o el mensaje de error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fechaEstandar('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fechaEstandar('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fechaEstandar('2024-01-01'); //Devuelve 01-01-2024
	 * ```
	 *
	 */
	public static function fechaEstandar($Fecha): DateTime|string {

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/**********************  Retorno datos  **********************/
		// Crea un objeto de fecha y aplica el formato de salida con año completo
		return date_format(date_create($Fecha), 'd-m-Y');
	}

	/************************************************************************************************************/
	/**
	 * Convierte una fecha al formato estándar corto (día-mes-año de dos dígitos).
	 *
	 * @param string $Fecha Fecha a formatear.
	 *
	 * @return DateTime|string Retorna la fecha en formato 'd-m-y' o el mensaje de error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fechaEstandarCorta('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fechaEstandarCorta('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fechaEstandarCorta('2024-01-01'); //Devuelve 01-01-24
	 * ```
	 *
	 */
	public static function fechaEstandarCorta($Fecha): DateTime|string {

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/**********************  Retorno datos  **********************/
		// Genera la representación de la fecha con el año abreviado a dos dígitos
		return date_format(date_create($Fecha), 'd-m-y');
	}

	/************************************************************************************************************/
	/**
	 * Normaliza una fecha al formato estándar de base de datos (AAAA-MM-DD).
	 * * @param string $Fecha La fecha de entrada a normalizar.
	 *
	 * @return DateTime|string La fecha en formato 'Y-m-d' o el resultado de la validación si falla.
	 *
	 * @example
	 * ```php
	 * $DataDate->fechaNormalizada('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fechaNormalizada('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fechaNormalizada('2024-01-01'); //Devuelve 2024-01-01
	 * ```
	 *
	 */
	public static function fechaNormalizada($Fecha): DateTime | string{

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/**********************  Retorno datos  **********************/
		// Sustituye barras inclinadas por guiones para asegurar la compatibilidad con date_create
		// Retorna la fecha formateada como Año-Mes-Día
		return date_format(date_create(str_replace('/', '-', $Fecha)), 'Y-m-d');

	}

	/************************************************************************************************************/
	/**
	 * Formatea una fecha para su uso en nomenclatura de archivos (AAAAMMDD).
	 * * @param string $Fecha La fecha de entrada a formatear.
	 *
	 * @return DateTime|string La cadena de texto con la fecha compacta o el error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fechaArchivos('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fechaArchivos('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fechaArchivos('2024-01-01'); //Devuelve 20240101
	 * ```
	 *
	 */
	public static function fechaArchivos($Fecha): DateTime | string{

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/**********************  Retorno datos  **********************/
		// Reemplaza separadores y genera una cadena numérica sin guiones ni espacios
		return date_format(date_create(str_replace('/', '-', $Fecha)), 'Ymd');

	}

	/************************************************************************************************************/
	/**
	 * Genera una representación textual de la fecha indicando el mes y el año.
	 * * @param string $Fecha La fecha de entrada a procesar.
	 *
	 * @return string Nombre del mes seguido del año (ej: "Enero del 2024").
	 *
	 * @example
	 * ```php
	 * $DataDate->fechaMesAno('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fechaMesAno('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fechaMesAno('2024-01-01'); //Devuelve Enero del 2024
	 * ```
	 *
	 */
	public static function fechaMesAno($Fecha): string{

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/********************** Si todo esta ok **********************/
		// Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$mes_c = new DateTime($Fecha);
		// Obtiene el año en formato de cuatro dígitos
		$ano = $mes_c->format('Y');
		// Obtiene el nombre del mes desde el array optionsMesLargo usando el índice numérico del mes (0-11)
		$mes = self::optionsMesLargo[$mes_c->format('m') - 1];

		/**********************  Retorno datos  **********************/
		// Retorna la cadena construida con el formato descriptivo solicitado
		return $mes.' del '.$ano;

	}

	/************************************************************************************************************/
	/**
	 * Obtiene el número del día del mes sin ceros iniciales.
	 *
	 * @param string $Fecha Fecha a procesar.
	 *
	 * @return string Número del día (1 a 31) o error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fecha2NdiaMes('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fecha2NdiaMes('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fecha2NdiaMes('2024-01-02'); //Devuelve 2
	 * ```
	 *
	 */
	public static function fecha2NdiaMes($Fecha): string {

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/********************** Si todo esta ok **********************/
		// Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$subdato = new DateTime($Fecha);

		/**********************  Retorno datos  **********************/
		// Retorna el día del mes sin ceros iniciales mediante el formato 'j'
		return $subdato->format("j");
	}

	/************************************************************************************************************/
	/**
	 * Obtiene el número del día del mes con dos dígitos (ceros iniciales).
	 *
	 * @param string $Fecha Fecha a procesar.
	 *
	 * @return string Número del día (01 a 31) o error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fecha2NdiaMesCon0('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fecha2NdiaMesCon0('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fecha2NdiaMesCon0('2024-01-01'); //Devuelve 01
	 * ```
	 *
	 */
	public static function fecha2NdiaMesCon0($Fecha): string {

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/********************** Si todo esta ok **********************/
		// Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$subdato = new DateTime($Fecha);

		/**********************  Retorno datos  **********************/
		// Retorna el día del mes con ceros iniciales mediante el formato 'd'
		return $subdato->format('d');
	}

	/************************************************************************************************************/
	/**
	 * Obtiene la representación numérica del día de la semana (ISO-8601).
	 *
	 * @param string $Fecha Fecha a procesar.
	 *
	 * @return string Número del día de la semana (1 para Lunes, 7 para Domingo).
	 *
	 * @example
	 * ```php
	 * $DataDate->fecha2NDiaSemana('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fecha2NDiaSemana('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fecha2NDiaSemana('2024-01-01'); //Devuelve 1
	 * ```
	 *
	 */
	public static function fecha2NDiaSemana($Fecha): string {

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/********************** Si todo esta ok **********************/
		// Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$subdato = new DateTime($Fecha);

		/**********************  Retorno datos  **********************/
		// Retorna el número del día de la semana según el estándar ISO-8601
		return $subdato->format('N');
	}

	/************************************************************************************************************/
	/**
	 * Obtiene el nombre completo del día de la semana en español.
	 *
	 * @param string $Fecha Fecha a procesar.
	 *
	 * @return string Nombre del día (ej: "Martes") o error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fecha2NombreDia('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fecha2NombreDia('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fecha2NombreDia('2024-01-02'); //Devuelve Martes
	 * ```
	 *
	 */
	public static function fecha2NombreDia($Fecha): string {

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/**********************  Retorno datos  **********************/
		// Utiliza el método fecha2NDiaSemana para obtener el índice (1-7)
		// Se resta 1 para ajustar al índice base cero del arreglo optionsDiaSemana
		// Obtiene el nombre del dia desde el array optionsDiaSemana usando el índice numérico del dia (0-6)
		return self::optionsDiaSemana[self::fecha2NDiaSemana($Fecha) - 1];
	}

	/************************************************************************************************************/
	/**
	 * Obtiene el número de la semana del año basándose en la fecha proporcionada.
	 *
	 * @param string $Fecha Fecha de entrada para el cálculo.
	 *
	 * @return string Número de la semana (ISO-8601) con ceros iniciales o error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fecha2NSemana('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fecha2NSemana('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fecha2NSemana('2024-01-01'); //Devuelve 01
	 * ```
	 *
	 */
	public static function fecha2NSemana($Fecha): string {

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/********************** Si todo esta ok **********************/
		// Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$subdato = new DateTime($Fecha);

		/**********************  Retorno datos  **********************/
		// Retorna el número de la semana del año (01 a 52/53)
		return $subdato->format("W");
	}

	/************************************************************************************************************/
	/**
	 * Obtiene la representación numérica del mes a partir de una fecha.
	 *
	 * @param string $Fecha Fecha de entrada.
	 *
	 * @return string Número del mes sin ceros iniciales (1 a 12) o error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fecha2NMes('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fecha2NMes('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fecha2NMes('2024-01-01'); //Devuelve 1
	 * ```
	 *
	 */
	public static function fecha2NMes($Fecha): string {

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/********************** Si todo esta ok **********************/
		// Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$subdato = new DateTime($Fecha);

		/**********************  Retorno datos  **********************/
		// Retorna el mes en formato numérico sin ceros a la izquierda
		return $subdato->format("n");
	}

	/************************************************************************************************************/
	/**
	 * Recupera el nombre completo del mes en español basándose en la fecha.
	 *
	 * @param string $Fecha Fecha de entrada.
	 *
	 * @return string Nombre largo del mes (ej: "Enero") o error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fecha2NombreMes('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fecha2NombreMes('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fecha2NombreMes('2024-01-01'); //Devuelve Enero
	 * ```
	 *
	 */
	public static function fecha2NombreMes($Fecha): string {

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/**********************  Retorno datos  **********************/
		// Utiliza el método fecha2NMes para obtener el índice numérico (1-12)
		// Se resta 1 para mapear correctamente al índice del arreglo constante optionsMesLargo
		// Obtiene el nombre del mes desde el array optionsMesLargo usando el índice numérico del mes (0-11)
		return self::optionsMesLargo[self::fecha2NMes($Fecha) - 1];
	}

	/************************************************************************************************************/
	/**
	 * Obtiene la abreviatura de tres letras del mes basándose en la fecha.
	 *
	 * @param string $Fecha Fecha de entrada.
	 *
	 * @return string Nombre corto del mes (ej: "Ene") o error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fecha2NombreMesCorto('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fecha2NombreMesCorto('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fecha2NombreMesCorto('2024-01-01'); //Devuelve Ene
	 * ```
	 *
	 */
	public static function fecha2NombreMesCorto($Fecha): string {

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/**********************  Retorno datos  **********************/
		// Obtiene el número del mes y accede al arreglo constante optionsMesCorto
		// El ajuste de índice (-1) es necesario para la correspondencia con arreglos base cero
		// Obtiene el nombre corto del mes desde el array optionsMesLargo usando el índice numérico del mes (0-11)
		return self::optionsMesCorto[self::fecha2NMes($Fecha) - 1];
	}

	/************************************************************************************************************/
	/**
	 * Extrae el año de una fecha proporcionada.
	 *
	 * @param string $Fecha Cadena de texto que representa la fecha.
	 *
	 * @return string Año en formato de cuatro dígitos (YYYY) o mensaje de error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fecha2Ano('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fecha2Ano('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fecha2Ano('2024-01-01'); //Devuelve 2024
	 * ```
	 *
	 */
	public static function fecha2Ano($Fecha): string{

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/********************** Si todo esta ok **********************/
		// Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$subdato = new DateTime($Fecha);

		/**********************  Retorno datos  **********************/
		// Retorna el año utilizando el formato 'Y' (ej: 2024)
		return $subdato->format('Y');

	}

	/************************************************************************************************************/
	/**
	 * Formatea una fecha al estilo estadounidense con el nombre del mes en inglés.
	 *
	 * @param string $Fecha Cadena de texto que representa la fecha.
	 *
	 * @return DateTime|string Fecha formateada (ej: "January 01 2024") o error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fechaGringa('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fechaGringa('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fechaGringa('2024-01-01'); //Devuelve January 01 2024
	 * ```
	 *
	 */
	public static function fechaGringa($Fecha): DateTime | string{

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/**********************  Retorno datos  **********************/
		// Genera un recurso de fecha y aplica el formato 'F d Y'
		// 'F' devuelve la representación textual completa del mes en inglés
		return date_format(date_create($Fecha), 'F d Y');

	}

	/************************************************************************************************************/
	/**
	 * Calcula y retorna la fecha correspondiente al último día del mes de la fecha dada.
	 *
	 * @param string $Fecha Cadena de texto que representa la fecha.
	 *
	 * @return string Fecha completa del último día del mes (YYYY-MM-DD) o error de validación.
	 *
	 * @example
	 * ```php
	 * $DataDate->fechaUltimoDiaMes('');           //Devuelve Sin fecha ingresada
	 * $DataDate->fechaUltimoDiaMes('a');          //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fechaUltimoDiaMes('2024-01-01'); //Devuelve '2024-01-31'
	 * ```
	 *
	 */
	public static function fechaUltimoDiaMes($Fecha): string{

		/********************** Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateDate($Fecha);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true) {
			return $dataVal;
		}

		/**********************  Retorno datos  **********************/
		// Utiliza el parámetro de formato 't' que devuelve el número de días del mes dado
		// Se combina con Y-m para reconstruir la fecha completa del último día
		return date("Y-m-t", strtotime($Fecha));

	}

	/************************************************************************************************************/
	/**
	 * Genera una cadena de texto descriptiva que incluye mes, día, año y hora.
	 *
	 * @param string $Fecha Cadena de texto con fecha y hora (Y-m-d H:i:s).
	 *
	 * @return string Fecha formateada en español (ej: "Diciembre 12 del 2023 13:17:59") o mensaje de error.
	 *
	 * @example
	 * ```php
	 * $DataDate->fullDate('');                    //Devuelve Sin fecha ingresada
	 * $DataDate->fullDate('a');                   //Devuelve El dato ingresado no es una fecha
	 * $DataDate->fullDate('2023-12-12 13:17:59'); //Devuelve Diciembre 12 del 2023 13:17:59
	 * ```
	 *
	 */
	public static function fullDate($Fecha): string{

		$Fecha = trim($Fecha);
		/**********************  Validaciones   **********************/
		// Comprobación de valor vacío o nulo
		if($Fecha=='' || $Fecha=='0000-00-00' || $Fecha=='00-00-0000'){   return 'Sin fecha ingresada';}
		// Validación de formato estricto incluyendo horas, minutos y segundos
		if(!self::validarFecha($Fecha, 'Y-m-d H:i:s')){ return 'El dato ingresado no es una fecha ('.$Fecha.')';}

		/********************** Si todo esta ok **********************/
		// Configuración de la zona horaria para asegurar la consistencia del objeto DateTime
		date_default_timezone_set('America/Santiago');

		// Instancia un objeto DateTime para la manipulación de los componentes de la fecha
		$NewFecha = new DateTime($Fecha);
		// Extrae el día del mes con ceros iniciales (01 a 31)
		$dia = $NewFecha->format('d');
		// Obtiene el año en formato de cuatro dígitos
		$ano = $NewFecha->format('Y');
		// Obtiene la hora en formato hh:mm:ss (01:00:00)
		$hora = $NewFecha->format('H:i:s');
		// Obtiene el nombre del mes desde el array optionsMesLargo usando el índice numérico del mes (0-11)
		$mes = self::optionsMesLargo[$NewFecha->format('m') - 1];

		/**********************  Retorno datos  **********************/
		// Construye y retorna la cadena final con el formato descriptivo
		return $mes.' '.$dia.' del '.$ano.' '.$hora;

	}

	/************************************************************************************************************/
	/**
     * Convierte un valor numérico a un formato de cadena con separadores de miles (punto)
     * y decimales (coma), rellenando con ceros según la precisión indicada.
     *
     * @param mixed $valor El número original que se desea formatear.
     * @param int $n_decimales Cantidad de dígitos decimales que se deben mostrar.
     *
     * @return string El número formateado o un mensaje de error si la validación falla.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->Cantidades(1250.85, 6); // Devuelve "1.250,850000"
	 * ```
	 *
     */
    public static function Cantidades($valor, $n_decimales): string{

        /**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal_1 = self::_validateValue($valor);
		$dataVal_2 = self::_validateInteger($n_decimales);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal_1 !== true){return $dataVal_1; }
		if ($dataVal_2 !== true){return $dataVal_2; }

        /**********************  Retorno datos  **********************/
        // Formatea el número utilizando punto para miles y coma para decimales
        return number_format($valor,$n_decimales,',','.');

    }

	/************************************************************************************************************/
	/**
     * Formatea un número como un correlativo de documento agregando ceros a la izquierda
     * hasta alcanzar la longitud deseada.
     *
     * @param mixed $valor El número de documento original.
     * @param int $n_ceros Longitud total deseada de la cadena resultante.
     *
     * @return string El número paddeado con ceros o un mensaje de error.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->nDoc(25, 7); // Devuelve "0000025"
	 * ```
	 *
     */
    public static function nDoc($valor, $n_ceros): string{

        /**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal_1 = self::_validateInteger($valor);
		$dataVal_2 = self::_validateInteger($n_ceros);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal_1 !== true){return $dataVal_1; }
		if ($dataVal_2 !== true){return $dataVal_2; }

        /**********************  Retorno datos  **********************/
        // Rellena la cadena por la izquierda con el carácter "0" hasta completar n_ceros
        return str_pad($valor, $n_ceros, "0", STR_PAD_LEFT);

    }

	/************************************************************************************************************/
	/**
     * Formatea un valor numérico como moneda, anteponiendo el símbolo de peso ($)
     * y aplicando formato de miles y decimales.
     *
     * @param mixed $valor El monto numérico a formatear.
     * @param int $n_decimales Cantidad de decimales requeridos.
     *
     * @return string Representación monetaria del valor.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->Valores(1500.85565, 2); // Devuelve "$ 1.500,86"
	 * ```
	 *
     */
    public static function Valores($valor, $n_decimales): string{

        /**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal_1 = self::_validateValue($valor);
		$dataVal_2 = self::_validateInteger($n_decimales);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal_1 !== true){return $dataVal_1; }
		if ($dataVal_2 !== true){return $dataVal_2; }

        /**********************  Retorno datos  **********************/
        // Concatena el símbolo de peso con el número formateado (punto para miles, coma para decimales)
        return '$ '.number_format($valor,$n_decimales,',','.');

    }

	/************************************************************************************************************/
	/**
     * Redondea un valor flotante al entero más cercano.
     *
     * @param mixed $valor El número que se desea redondear.
     *
     * @return string|float|int El valor redondeado o un mensaje de error.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->valoresEnteros(1500.85); // Devuelve 1501
	 * ```
	 *
     */
    public static function valoresEnteros($valor): string | float | int {

        /**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateValue($valor);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true){return $dataVal; }

        /**********************  Retorno datos  **********************/
        // Utiliza la función round para redondear al entero superior o inferior más próximo
        return round($valor);

    }

	/************************************************************************************************************/
	/**
     * Redondea un valor decimal hacia arriba (techo) al siguiente entero.
     *
     * @param mixed $valor El número a procesar.
     *
     * @return string|float|int El valor redondeado hacia arriba o un mensaje de error.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->valoresComparables(1500.85); // Devuelve 1501
	 * ```
	 *
     */
    public static function valoresComparables($valor): string | float | int {

        /**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateValue($valor);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true){return $dataVal; }

        /**********************  Retorno datos  **********************/
        // Aplica la función ceil para obtener el entero inmediato superior
        return ceil($valor);

    }

	/************************************************************************************************************/
	/**
     * Trunca un valor numérico eliminando su parte decimal (redondeo hacia abajo).
     *
     * @param mixed $valor El número a truncar.
     *
     * @return string|float|int El entero resultante o un mensaje de error.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->valoresTruncados(1500.85); // Devuelve 1500
	 * ```
	 *
     */
    public static function valoresTruncados($valor): string | float | int {

        /**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateValue($valor);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true){return $dataVal; }

        /**********************  Retorno datos  **********************/
        // Aplica la función floor para descartar los decimales sin redondear hacia arriba
        return floor($valor);

    }

	/************************************************************************************************************/
	/**
     * Formatea un número mostrando únicamente los decimales significativos existentes,
     * con un límite máximo de 6 dígitos decimales.
     *
     * @param mixed $valor El número a formatear.
     *
     * @return string|float|int El número redondeado a sus decimales reales (máx 6) o mensaje de error.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->cantidadesDecimalesJustos(1500.85000); //Devuelve 1500.85
	 * ```
	 *
     */
    public static function cantidadesDecimalesJustos($valor): string | float | int {

        /**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateValue($valor);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true){return $dataVal; }

        /********************** Si todo esta ok **********************/
        // Conversión a cadena para análisis de posición de caracteres
        $valor_str = (string) $valor;

        // Calcula la cantidad de dígitos después del punto decimal
        // substr(strrchr()) extrae la parte decimal; strlen cuenta su longitud
        $dec = strlen(substr(strrchr($valor_str, '.'), 1));

        // Establece un techo técnico de 6 decimales para el redondeo
        if ($dec >= 6) {$dec = 6;}

        /**********************  Retorno datos  **********************/
        // Retorna el valor redondeado a la precisión calculada dinámicamente
        return round($valor, $dec);

    }

	/************************************************************************************************************/
	/**
     * Prepara un valor numérico para ser compatible con el formato de celdas de Excel
     * (Localización ES), sustituyendo el punto decimal por coma.
     *
     * @param mixed $valor El número original con punto decimal.
     *
     * @return string|float|int El valor con formato de coma decimal o "0".
	 *
	 * @example
	 * ```php
	 * $DataNumbers->cantidadesExcel(1500.85); // Devuelve "1500,85"
	 * ```
	 *
     */
    public static function cantidadesExcel($valor): string | float | int {

        /**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateValue($valor);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true){return $dataVal; }

        /**********************  Retorno datos  **********************/
        // Reemplaza el separador decimal estándar (.) por el formato usado frecuentemente en Excel ES (,)
        return str_replace('.', ',', $valor);

    }

	/************************************************************************************************************/
	/**
     * Normaliza un valor numérico para entornos de Google (Sheets/Cloud) asegurando
     * el uso del punto como separador decimal.
     *
     * @param mixed $valor El valor numérico que puede contener comas.
     *
     * @return string|float|int El valor con punto decimal o mensaje de error.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->cantidadesGoogle(1500.85); //Devuelve 1500.85
	 * ```
	 *
     */
    public static function cantidadesGoogle($valor): string | float | int {

        /**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateValue($valor);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true){return $dataVal; }

        /**********************  Retorno datos  **********************/
        // Asegura que el separador decimal sea un punto, reemplazando comas si existieran
        return str_replace(',', '.', $valor);

    }

	/************************************************************************************************************/
	/**
     * Aplica un formato visual de máscara telefónica a una cadena de números.
     *
     * @param string $Phone Cadena con el número de teléfono.
     *
     * @return string Teléfono formateado como "(+XX) X XXXX XXXX" o mensaje de error.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->formatPhone('+56911265984'); //Devuelve (+56) 9 1126 5984
	 * ```
	 *
     */
    public static function formatPhone($Phone): string{

		/**********************  Validaciones   **********************/
        // Validaciones de existencia y longitud mínima/máxima
        if(!isset($Phone) || $Phone==''){ return 'Sin datos ingresados';}
        if(strlen($Phone)<=7){            return 'Numero demasiado corto, tiene '.strlen($Phone).' numeros y debe tener al menos 9';}
        if(strlen($Phone)>=13){           return 'Numero demasiado largo, tiene '.strlen($Phone).' numeros y debe tener no mas de 11';}

		/********************** Si todo esta ok **********************/
        // Llama a la función interna para estandarizar el prefijo y limpiar caracteres
        $myPhone = self::normalizarPhone($Phone);

		/**********************  Retorno datos  **********************/
        // Divide la cadena normalizada en bloques usando substr para aplicar la máscara final
        return sprintf("(%s) %s %s %s",
            substr($myPhone, 0, 3),
            substr($myPhone, 3, 1),
            substr($myPhone, 4, 4),
            substr($myPhone, 8, 4));

    }

	/************************************************************************************************************/
	/**
     * Estandariza un número de teléfono al formato internacional chileno (+56).
     *
     * @param string $Phone El número de teléfono en diversos posibles formatos.
     *
     * @return string El número normalizado comenzando con +56.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->normalizarPhone('+56911265984'); //Devuelve +56 9 1126 5984
	 * ```
	 *
     */
    public static function normalizarPhone($Phone): string{

        /**********************  Validaciones   **********************/
        // Validaciones de longitud y presencia de datos
        if(!isset($Phone) || $Phone==''){ return 'Sin datos ingresados';}
        if(strlen($Phone)<=7){            return 'Numero demasiado corto, tiene '.strlen($Phone).' numeros y debe tener al menos 9';}
        if(strlen($Phone)>=13){           return 'Numero demasiado largo, tiene '.strlen($Phone).' numeros y debe tener no mas de 11';}

        /********************** Si todo esta ok **********************/
        // Corrección para números de 8 dígitos (formato antiguo) anteponiendo el dígito 9
        if(strlen($Phone)==8){$Phone = '9'.$Phone;}

        $myNumber = $Phone;
        // Remueve cualquier carácter que no sea un dígito numérico
        $Phone = preg_replace('/\D/', '', $myNumber);

        // Lógica condicional para determinar cómo aplicar el prefijo +56 según el inicio de la cadena original
        if (strpos($myNumber, '+56') === 0) {
            $myPhone = '+56' . substr($Phone, 2);
        } elseif (strpos($myNumber, '56') === 0) {
            $myPhone = '+56' . substr($Phone, 2);
        } elseif (strpos($myNumber, '+') === 0) {
            $myPhone = $myNumber;
        } else {
            // Si no tiene prefijo internacional detectado, se asume local y se agrega +56
            $myPhone = '+56' . $Phone;
        }

        /**********************  Retorno datos  **********************/
        // Retornar numero fono normalizado
        return $myPhone;

    }

	/************************************************************************************************************/
	/**
     * Agrega un cero a la izquierda para números naturales menores a 10.
     *
     * @param mixed $valor El número a evaluar.
     *
     * @return string El número formateado con dos dígitos (ej: "01") o el original.
	 *
	 * @example
	 * ```php
	 * $DataNumbers->numberInit0(1); //Devuelve 01
	 * ```
	 *
     */
    public static function numberInit0($valor): string{

        /**********************  Validaciones   **********************/
		// Ejecuta la validación interna del formato y consistencia de la fecha recibida
		$dataVal = self::_validateValue($valor);
		// Si la validación devuelve un valor distinto a true, se retorna el error/resultado de la validación
		if ($dataVal !== true){return $dataVal; }

        /**********************  Retorno datos  **********************/
        // Evaluación por operador ternario: si es menor a 10, concatena el carácter "0"
        return ($valor<10) ? '0'.$valor : $valor;

    }

	/************************************************************************************************************/
	/**
     * Obtiene la fecha actual configurada para la zona horaria de Chile.
     * Establece el huso horario 'America/Santiago' y retorna la fecha en formato ISO 8601 extendido.
     *
     * @return string Fecha actual en formato YYYY-MM-DD (ej: 2026-04-04).
	 *
	 * @example
	 * ```php
	 * $ServerServer->fechaActual(); //devuelve la fecha actual con formato 2024-07-01
	 * ```
	 *
     */
    public static function fechaActual($format = "Y-m-d"): string {

        /********************** Si todo esta ok **********************/
        // Establecer la zona horaria predeterminada a Chile para asegurar consistencia en los datos
        date_default_timezone_set('America/Santiago');

        /********************** Retorno datos  **********************/
        // Devolvemos la fecha actual utilizando el separador de guion
        return date($format);

    }

    /************************************************************************************************************/
	/**
     * Obtiene la fecha actual de Chile sin caracteres separadores.
     * Útil para la generación de nombres de archivos, folios o procesos que requieren un formato compacto.
     *
     * @return string Fecha actual en formato YYYYMMDD (ej: 20260404).
	 *
	 * @example
	 * ```php
	 * $ServerServer->fechaActualAlternative(); //devuelve la fecha actual con formato 20240701
	 * ```
	 *
     */
    public static function fechaActualAlternative(): string {

        /********************** Si todo esta ok **********************/
        // Configura la zona horaria local de Chile
        date_default_timezone_set('America/Santiago');

        /********************** Retorno datos  **********************/
        // Retorna la cadena numérica representativa de la fecha
        return date("Ymd");

    }

    /************************************************************************************************************/
	/**
     * Obtiene la hora actual configurada para la zona horaria de Chile.
     * Retorna la representación del tiempo en formato de 24 horas con separadores de dos puntos.
     *
     * @return string Hora actual en formato HH:ii:ss (ej: 18:28:58).
	 *
	 * @example
	 * ```php
	 * $ServerServer->horaActual(); //devuelve la hora actual con formato 18:28:58
	 * ```
	 *
     */
    public static function horaActual($format = "H:i:s"): string {

        /********************** Si todo esta ok **********************/
        // Asegura que la hora corresponda al huso horario de Chile
        date_default_timezone_set('America/Santiago');

        /********************** Retorno datos  **********************/
        // Genera la cadena de tiempo con formato estándar de base de datos
        return date($format);

    }

    /************************************************************************************************************/
	/**
     * Obtiene la hora actual de Chile utilizando guiones como separadores.
     * Formato alternativo diseñado para compatibilidad con sistemas de archivos que restringen el uso de ':'.
     *
     * @return string Hora actual en formato HH-ii-ss (ej: 18-28-58).
	 *
	 * @example
	 * ```php
	 * $ServerServer->horaActualAlternative(); //devuelve la hora actual con formato 18-28-58
	 * ```
	 *
     */
    public static function horaActualAlternative(): string {

        /********************** Si todo esta ok **********************/
        // Sincronización con la hora local de Chile
        date_default_timezone_set('America/Santiago');

        /********************** Retorno datos  **********************/
        // Retorna la hora con separadores de guion
        return date("H-i-s");

    }

    /************************************************************************************************************/
	/**
     * Obtiene el número del día actual del mes en curso.
     * Retorna el día sin ceros iniciales, basándose en la zona horaria de Chile.
     *
     * @return string Día del mes (1 a 31).
	 *
	 * @example
	 * ```php
	 * $ServerServer->diaActual(); //devuelve 1 (para la fecha 2024-07-01)
	 * ```
	 *
     */
    public static function diaActual(): string {

        /********************** Si todo esta ok **********************/
        // Configuración regional de horario
        date_default_timezone_set('America/Santiago');

        /********************** Retorno datos  **********************/
        // Extrae el componente del día del mes sin relleno de ceros
        return date("j");

    }

    /************************************************************************************************************/
	/**
     * Obtiene el número de la semana actual del año.
     * Utiliza el estándar ISO-8601 donde las semanas comienzan en lunes.
     *
     * @return string Número de la semana del año (01 a 52/53).
	 *
	 * @example
	 * ```php
	 * $ServerServer->semanaActual(); //devuelve 27 (para la fecha 2024-07-01)
	 * ```
	 *
     */
    public static function semanaActual(): string {

        /********************** Si todo esta ok **********************/
        // Sincronización horaria
        date_default_timezone_set('America/Santiago');

        /********************** Retorno datos  **********************/
        // Retorna el número de semana anual
        return date("W");

    }

    /************************************************************************************************************/
	/**
     * Obtiene el número del mes actual.
     * Retorna el valor numérico del mes sin ceros iniciales.
     *
     * @return string Número del mes (1 a 12).
	 *
	 * @example
	 * ```php
	 * $ServerServer->mesActual(); //devuelve 7 (para la fecha 2024-07-01)
	 * ```
	 *
     */
    public static function mesActual(): string {

        /********************** Si todo esta ok **********************/
        // Configuración de zona horaria local
        date_default_timezone_set('America/Santiago');

        /********************** Retorno datos  **********************/
        // Extrae el mes actual en formato numérico simple
        return date("n");

    }

    /************************************************************************************************************/
	/**
     * Obtiene el año actual en formato de cuatro dígitos.
     *
     * @return string Año actual (ej: 2026).
	 *
	 * @example
	 * ```php
	 * $ServerServer->anoActual(); //devuelve 2024 (para la fecha 2024-07-01)
	 * ```
	 *
     */
    public static function anoActual(): string {

        /********************** Si todo esta ok **********************/
        // Asegura que la fecha base sea la de Chile
        date_default_timezone_set('America/Santiago');

        /********************** Retorno datos  **********************/
        // Retorna el año completo (4 dígitos)
        return date("Y");

    }

    /************************************************************************************************************/
	/**
     * Ejecuta comandos administrativos o peticiones web en segundo plano en el servidor.
     *
     * Permite delegar tareas pesadas o de configuración (bloqueo de IP, wget) al sistema operativo.
     * Implementa validaciones de seguridad para IPs y URLs antes de la ejecución.
     *
     * @param string $tarea Dato de entrada (IP o URL) según el tipo de tarea.
     * @param int $Type Identificador del tipo de tarea (1: Iptables, 2: Wget, 3: Personalizado).
     *
     * @return array Resultado de la ejecución con estado 'success' y mensaje descriptivo.
	 *
	 * @example
	 * ```php
	 * $ServerServer->tareasServer(https://www.ejemplo.com?param1=1&param2=2&param3=3);
	 * ```
	 *
     */
    public static function tareasServer(string $tarea, int $Type): array {

        /********************** Si todo esta ok **********************/
        try {
            // Validación de integridad: la cadena de tarea no debe ser nula o vacía
            if (empty($tarea)) {
                return ['success' => false, 'data' => 'La tarea no puede estar vacía.'];
            }

            // Selección del comando de sistema a construir basado en el tipo de operación
            switch ($Type) {
                /*************************/
                // Caso 1: Gestión de seguridad mediante bloqueo de IP en el firewall (iptables)
                case 1:
                    if(filter_var(trim($tarea), FILTER_VALIDATE_IP)){
                        // Genera un script multilínea para añadir la IP al DROP y persistir cambios
                        $command = "
                        # Agrega la IP a la lista negra (DROP todo el tráfico entrante)
                        iptables -A INPUT -s ".$tarea." -j DROP

                        # Guarda los cambios (puede variar según la distribución)
                        if command -v netfilter-persistent &> /dev/null; then
                            netfilter-persistent save
                        elif command -v iptables-save &> /dev/null; then
                            iptables-save > /etc/iptables/rules.v4
                        fi";
                    }else{
                        return ['success' => false, 'data' => 'Verifique el dato solicitado, no es una IP.'];
                    }
                    break;
                /*************************/
                // Caso 2: Ejecución de petición HTTP asíncrona mediante wget
                case 2:
                    if(filter_var(trim($tarea), FILTER_VALIDATE_URL)){
                        // Escapa el argumento de la URL para prevenir inyecciones de comandos shell
                        $urlSeguro = escapeshellarg($tarea);
                        // Ejecuta wget en modo silencioso y en segundo plano (&)
                        $command = "/usr/bin/wget -N -q $urlSeguro &";
                    }else{
                        return ['success' => false, 'data' => 'Verifique el dato solicitado, no es una URL.'];
                    }
                    break;
                /*************************/
                // Caso 3: Espacio reservado para lógica de comandos adicionales
                case 3:
                    //otro comando
                    break;
            }

            // Intento de ejecución del comando construido en la terminal del sistema operativo
            try {
                // Ejecución del comando y captura de la salida estándar
                $resultado = shell_exec($command);

                // Si shell_exec retorna null, indica que hubo un error o el comando no produjo salida
                if ($resultado === null) {
                    return ['success' => false, 'data' => 'Error al ejecutar el comando. No se recibió salida.'];
                }

                // Confirmación de envío del comando al sistema
                return ['success' => true, 'data' => 'Ejecucion correcta'];
            } catch (\Throwable $th) {
                // Captura de errores fatales durante el proceso de ejecución shell
                return ['success' => false, 'data' => $th->getMessage(), 'code' => $th->getCode()];
            }

        } catch (Exception $e) {
            // Manejo de excepciones generales y sanitización de mensajes de error
            return ['success' => false, 'data' => 'Ocurrió un error:'.htmlspecialchars($e->getMessage())];
        }

    }

    /************************************************************************************************************/
	/**
     * Recupera y normaliza los índices informativos del arreglo global $_SERVER.
     *
     * Extrae una lista predefinida de variables de entorno, cabeceras y rutas del servidor,
     * transformándolas en un objeto para facilitar el acceso orientado a objetos.
	 *
     * @return object Objeto que contiene las claves de $_SERVER solicitadas o un mensaje de error.
	 *
	 * @example
	 * ```php
	 * $ServerServer->indicesServer()->PHP_SELF;
     * $ServerServer->indicesServer()->GATEWAY_INTERFACE;
     * $ServerServer->indicesServer()->SERVER_NAME;
     * $ServerServer->indicesServer()->SERVER_PROTOCOL;
     * $ServerServer->indicesServer()->REQUEST_TIME;
	 * ```
	 *
     */
    public static function indicesServer(): object {

        /********************** Si todo esta ok **********************/
        try {
            // Definición de las claves de interés dentro del entorno global de ejecución
            $claves = [
                'PHP_SELF', 'argv', 'argc', 'GATEWAY_INTERFACE', 'SERVER_ADDR', 'SERVER_NAME',
                'SERVER_SOFTWARE', 'SERVER_PROTOCOL', 'REQUEST_METHOD', 'REQUEST_TIME',
                'REQUEST_TIME_FLOAT', 'QUERY_STRING', 'DOCUMENT_ROOT', 'HTTP_ACCEPT',
                'HTTP_ACCEPT_CHARSET', 'HTTP_ACCEPT_ENCODING', 'HTTP_ACCEPT_LANGUAGE',
                'HTTP_CONNECTION', 'HTTP_HOST', 'HTTP_REFERER', 'HTTP_USER_AGENT', 'HTTPS',
                'REMOTE_ADDR', 'REMOTE_HOST', 'REMOTE_PORT', 'REMOTE_USER', 'REDIRECT_REMOTE_USER',
                'SCRIPT_FILENAME', 'SERVER_ADMIN', 'SERVER_PORT', 'SERVER_SIGNATURE',
                'PATH_TRANSLATED', 'SCRIPT_NAME', 'REQUEST_URI', 'PHP_AUTH_DIGEST',
                'PHP_AUTH_USER', 'PHP_AUTH_PW', 'AUTH_TYPE', 'PATH_INFO', 'ORIG_PATH_INFO'
            ];

            $datos = [];

            // Itera sobre las claves definidas para extraer los valores existentes
            foreach ($claves as $clave) {
                // Asigna el valor de $_SERVER si existe, de lo contrario establece null
                $datos[$clave] = array_key_exists($clave, $_SERVER) ? $_SERVER[$clave] : null;
            }

            /********************** Retorno datos  **********************/
            // Casteo del array resultante a objeto estándar
            return (object) $datos;

        } catch (Throwable $e) {
            // Registro del error en el log del sistema en caso de fallo crítico
            error_log("Error al obtener datos del servidor: " . $e->getMessage());
            return (object) ['error' => 'No se pudieron obtener los datos del servidor.'];
        }

    }

	/*******************************************************************************************************************/
	/*                                                                                                                 */
	/*                                              Metodos Internos                                                   */
	/*                                                                                                                 */
	/*******************************************************************************************************************/
    /************************************************************************************************************/
	private static function _validateDate($Fecha){

		/**********************  Validaciones   **********************/
		if($Fecha=='' || $Fecha=='0000-00-00' || $Fecha=='00-00-0000'){   return 'Sin fecha ingresada';}
		if(!self::validarFecha($Fecha)){                                 return 'El dato ingresado no es una fecha ('.$Fecha.')';}

		/********************** Si todo esta ok **********************/
		/**********************  Retorno datos  **********************/
		return true;

	}
    /************************************************************************************************************/
	private static function _validateValue($Data){

		/**********************  Validaciones   **********************/
        // Retorno inmediato si el valor es nulo, cadena vacía o numéricamente cero
        if ($Data=='' || $Data==0) {   return '0'; }
        // Validación de tipos de datos mediante el componente externo DataValidations
        if (!self::validarNumero($Data)) {
            return 'El dato ingresado no es un numero ('.$Data.')';
        }

		/**********************  Retorno datos  **********************/
		return true;

	}
    /************************************************************************************************************/
	private static function _validateInteger($Data){

		/**********************  Validaciones   **********************/
        // Retorno inmediato si el valor es nulo, cadena vacía o numéricamente cero
        if ($Data=='' || $Data==0){return 'Sin datos ingresados'; }
        // Validación de tipos de datos mediante el componente externo DataValidations
        if (!self::validarNumero($Data) || !self::validarEntero($Data)) {
            return 'El dato ingresado no es un numero ('.$Data.')';
        }

		/**********************  Retorno datos  **********************/
		return true;

	}


}

<?php
/**
 * Strings for component 'paperattendance', language 'es'
 *
 * @package   paperattendance
 */


$string['pluginname']="Asistencias en Papel";
$string['notallowedupload']="No tienes permisos para subir asistencias";
$string['uploadtitle']="Asistencias en Papel";
$string['uploadsuccessful']="Asistencia subida correctamente";
$string['header']="Formulario de subida pdf";
$string['uploadteacher']="Solicitante";
$string['uploadrule']="Campo obligatorio, debe ser .pdf";
$string['uploadplease']="Porfavor ingrese un pdf en el seleccionador de archivos";
$string['uploadfilepicker']="Pdf de la Asistencia";
$string['selectteacher']="Seleccione profesor";
$string['viewmodules']="Módulos Asistencias en papel";
$string['modulestitle']="Módulos";
$string['modulename']="Nombre del módulo";
$string['required']="Campo requerido";
$string['initialtime']="Hora de inicio";
$string['endtime']="Hora de fin";
$string['addmoduletitle']="Añadir módulo";
$string['nomodules']="No existen módulos para mostrar";
$string['addmodule']="Asistencias en papel Añadir módulo";
$string['delete']="Borrar módulo";
$string['doyouwantdeletemodule']="¿Está seguro que desea eliminar el módulo?";
$string['edit']="Editar módulo";
$string['doyouwanteditmodule']="¿Está seguro que desea editar el módulo?";
$string['editmodule']="Asistencias en papel Editar módulo";
$string['editmoduletitle']="Editar módulo";
$string['printtitle']="Imprimir lista de asistencia";
$string['printgoback']="Volver";
$string['downloadprint']="Imprimir lista";
$string['selectteacher']="Seleccione Profesor";
$string['requestor']="Solicitante";
$string['attdate']="Fecha de la sesión";
$string['modulescheckbox']="Módulos";
$string['pleaseselectteacher']="Por favor seleccione profesor primero";
$string['pleaseselectdate']="Por favor seleccione una fecha válida";
$string['pleaseselectmodule']="Por favor seleccione al menos un módulo";
$string['pdfattendance']="Asistencia";
$string['pleaseselectattendance']="Por favor seleccione asistencia";
$string['absentattendance']="Ausente";
$string['presentattendance']="Presente";
$string['hashtag']="#";
$string['student']="Alumno";
$string['mail']="Correo";
$string['attendance']="Asistencia";
$string['setting']="Ajustes";
$string['nonselectedstudent']="Estudiante no seleccionado";
$string['nonexiststudent']="Estudiante no existe";
$string['date']="Fecha";
$string['time']="Hora";
$string['scan']="Scan";
$string['studentsattendance']="Asistencia Alumnos";
$string['see']="Ver";
$string['seestudents']="Ver alumnos";
$string['historytitle']="Historial de Asistencia";
$string['historyheading']="Historial de Asistencia";
$string['nonexistintingrecords']="No existen registros";
$string['back']="Atrás";
$string['download']="Descargar";
$string['downloadassistance']="Descargar asistencia";
$string['backtocourse']="Volver al curso";
$string['edithistory']="Editar";
$string['pdfextensionunrecognized']="Extension del pdf no reconocido";
$string['courses']="Cursos";
$string['sunday']="Domingo";
$string['monday']="Lunes";
$string['tuesday']="Martes";
$string['wednesday']="Miercoles";
$string['thursday']="Jueves";
$string['friday']="Viernes";
$string['saturday']="Sabado";
$string['january']="enero";
$string['february']="febrero";
$string['march']="marzo";
$string['april']="abril";
$string['may']="mayo";
$string['june']="junio";
$string['july']="julio";
$string['august']="agosto";
$string['september']="septiembre";
$string['october']="octubre";
$string['november']="noviembre";
$string['december']="diciembre";
$string['of']=" de ";
$string['from']=" del ";
$string['error']="ACCESO DENEGADO - Alumno no matriculado en el curso";
$string['couldntsavesession']="Error, la sesión dado los módulos entregados ya existe";
$string['couldntreadqrcode']="No se pudo leer el código QR, asegurese que éste sea legible y no esté rayado";
$string['omegasync']="Omega";
$string['synchronized']="Sincronizado";
$string['unsynchronized']="Sin sincronizar";
$string['module']="Módulo";


// Settings
$string['settings']="Configuración Básica";
$string['grayscale']="Escala de grises";
$string['grayscaletext']="Valor máximo para discernir entre presente y ausente, más bajo es más oscuro.";
$string['minuteslate']="Minutos de atraso";
$string['minuteslatetext']="Minutos máximos para atraso en imprimir lista en módulo actual";
$string['maxfilesize']="Tamaño máximo de archivo escaneado";
$string['maxfilesizetext']="Tamaño máximo de archivo escaneado en bytes";
$string['enrolmethod']="Métodos de matriculación por defecto";
$string['enrolmethodpro']="Los métodos de matriculación que por defecto se seleccionarán al generar una lista de asistencias. Para producción se requiere 'database,meta'.";
$string['token']="Token de Omega";
$string['tokentext']="Token de Omega para uso de webapi";
$string['omegacreateattendance']="Url Omega CreateAttendance";
$string['omegacreateattendancetext']="Url Omega CreateAttendance webapi";
$string['omegaupdateattendance']="Url Omega UpdateAttendance";
$string['omegaupdateattendancetext']="Url Omega UpdateAttendance webapi";
$string['omegagetmoduloshorarios']="Url Omega Get Modulos Horarios";
$string['omegagetmoduloshorariostext']="Url Omega Get Modulos Horarios webapi";


// Task
$string['task']="Procesar PDFs";

// Capabilities
$string["paperattendance:print"] = "Ver impresiones de listas";
$string["paperattendance:upload"] = "Subir listas escaneadas";
$string["paperattendance:history"] = "Ver historial de asistencias";
$string["paperattendance:manageattendance"] = "Configuración asistencias en papel";
$string["paperattendance:modules"] = "Administrar modulos";
$string["paperattendance:teacherview"] = "Vista del profesor";
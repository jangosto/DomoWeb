//Para BBDD
#include <my_global.h>
#include <mysql.h>
#include <time.h>
//Para uso global
#include <stdio.h>
#include <string.h>
#include <stdbool.h> 
//Para socket y UART
#include <stdlib.h>
#include <unistd.h>		//Used for UART
#include <fcntl.h>		//Used for UART
#include <termios.h>		//Used for UART
#include <pthread.h>
#include <sys/types.h> 
#include <sys/socket.h>
#include <netinet/in.h>
//Para sincronización de procesos
#include <semaphore.h>

//Constantes de hilos
#define HILO_LEE_SOCKET 0
#define HILO_LEE_UART 1
#define HILO_MONITOR_VAR 2
#define HILO_TIMER 3

//Constantes de socket
#define PUERTO 11000
#define NUM_CONEXIONES 3

//Constantes de BBDD
#define BBDD_HOST "localhost"
#define BBDD_USER "root"
#define BBDD_PASS "euitt"
#define BBDD_NAME "domoweb"

#define BBDD_ID_TIPOVAR_TEMPERATURA 1
#define BBDD_ID_TIPOVAR_INTENSIDADLUZ 2
#define BBDD_ID_TIPOVAR_AUTOLUZ_ACTIVADO_USER 3
#define BBDD_ID_TIPOVAR_AUTOTEMP_ACTIVADO_USER 4
#define BBDD_ID_TIPOVAR_LUZACTION_INTENSIDAD_USER 5


//#define BBDD_ID_TIPOVAR_LUZACTION_INTENSIDAD_SYS 7
//#define BBDD_ID_TIPOVAR_TEMPACTION_TEMPDESEADA_USER 6
//#define BBDD_ID_TIPOVAR_TEMPACTION_TEMPDESEADA_SYS 8

//Identificadores de Funcionalidad
#define CONTROL_LUZ "_cl_"
#define CONTROL_TEMPERATURA "_ct_"
#define SENSOR_LUZ "_sl_"
#define SENSOR_TEMPERATURA "_st_"


//Constantes de Mutex
#define MUTEX_UART_TX 0
#define MUTEX_TIMER_CONTADOR 1
#define MUTEX_VARIABLE_MENSAJEGLOBAL 2
#define MUTEX_VARIABLE_TIMERACTIVADO 3

//Constantes de Semaphores
#define SEMAPHORE_UART_RX 0
#define SEMAPHORE_TIMER_ACTIVAR 1

//Constantes de mensajes
#define PETICION_TEMP "TEMP"
#define PETICION_ILUZ "MILZ"

#define ORDEN_FIJAR_ILUZ "LZFR"

#define ORDEN_AUTO_TEMPERATURA_APAGAR "AUTTMPOFF"
#define ORDEN_AUTO_TEMPERATURA_ENCENDER "AUTTMPON"
#define ORDEN_TEMPERATURA_DESEADA_FIJAR "TMPDS"
#define ORDEN_AUTO_LUZ_APAGAR "AUTLZOFF"
#define ORDEN_AUTO_LUZ_ENCENDER "AUTLZON"
#define ORDEN_LUZ_DESEADA_FIJAR "ILZDS"

#define ORDEN_LUZ_ENCENDER "LZON"
#define ORDEN_LUZ_APAGAR "LZOFF"
#define ORDEN_CALEFACCION_ENCENDER "CLON"
#define ORDEN_CALEFACCION_APAGAR "CLOFF"

#define ORDEN_RESET_SISTEMA "RST"

//Constantes de Direccionamiento
#define DIRECCION_SENSOR_SALON "276B"
#define DIRECCION_ACTUADOR_SALON "276C"
#define DIRECCION_GLOBAL "FFFF"

//Constantes de Monitorización
#define NUM_INTENTOS_CONSULTA 10
#define PERIODO_MONITOR 60 //Periodo de monitorizaión en s

//Constantes de estructura de mensajes
#define MAX_NUM_PARTES_MENSAJE 3
#define MAX_LONGITUD_MENSAJE 30
#define INDICE_MENSAJE 0
#define INDICE_DIRECCION 1
#define INDICE_ORDEN 2
#define INDICE_VALOR 3

//Constantes de Comandos bash
#define REBOOT_SYSTEM "sudo shutdown -r now"


typedef struct{
	int descriptor_socket;
	struct sockaddr_in info_maquina;
	int error;
}socket_datos;

typedef struct{
	int uart;
	int socket;
	MYSQL* bbdd;
}manejadores;

//Hilos
void* leeSocket (void *arg);
void* leeUART(void *arg);
void* monitorizarVariables (void *arg);
void* controltimer (void *arg);

//Funciones de socket
socket_datos crearSocket(unsigned short puerto, int numconexiones);

//Funciones de UART
int abrir_uart();
int enviarDato (char datoOut[],int uart);

//Funciones de BBDD
void cerrarBBDD (MYSQL* descriptorBBDD);
MYSQL* abrirBBDD (char maquina[],char usuario[],char password[], char baseDatos[]);
int registrarDatoMonitor(MYSQL* conect, int idvariable, double valor, int timestamp, int idzona);
int sacarIdZona(MYSQL* conect, int idControlador);
int tomarUltimoValorIntensidadLuzFijada(MYSQL* conect,int idControlador);
int tomarUltimoValorAutoLuz(MYSQL* conect,int idControlador);
void tomarControladoresLuz(MYSQL* conect,int direcciones[]);
void tomarSensoresLuz(MYSQL* conect,int direcciones[]);

//Funciones de control de mensaje
void sacarDatosMensaje(char buffer[],char datosMensaje[MAX_NUM_PARTES_MENSAJE+1][MAX_LONGITUD_MENSAJE]);
void fijarMensajesMonitor(void);
void sacarMensajeCabecera(char buffer[],char mensaje[]);

//Funciones de control del sistema
void iniciarControladoresLuz(int uart);
void iniciarSensoresLuz(int uart);


pthread_t idhilos[10];
pthread_mutex_t mutex[10];
sem_t semaforo[10];
char mensajeRX[MAX_LONGITUD_MENSAJE];

char mensajesMonitor[30][MAX_LONGITUD_MENSAJE];

char mensajeGlobal[MAX_NUM_PARTES_MENSAJE+1][MAX_LONGITUD_MENSAJE];

//Contador del TIMER
unsigned char timerContador = 250;
unsigned char timerActivado = 0;

//Variables de información de estado de configuración del sistema
char auto_luz_state = 0;
char auto_temp_state = 0;
char luz_state = 0;



/************************
*   FUNCION PRINCIPAL   *
************************/

void main(void){
	MYSQL* conector_bbdd=NULL;
	socket_datos servidor;
	int descriptor_uart = -1;
	manejadores hiloDatos;
	pthread_t idhilos[10];
	int errorHilo;
	
	servidor = crearSocket(PUERTO,NUM_CONEXIONES);
	descriptor_uart = abrir_uart();
	
	hiloDatos.uart = descriptor_uart;
	hiloDatos.socket = servidor.descriptor_socket;

	//*****************************
	// Sistemas de Sincronización *
	//*****************************

	//mutex para TX de la UART
	if (pthread_mutex_init(&(mutex[MUTEX_UART_TX]), NULL) != 0)
	{
		printf("\n[main] Inicialización de Mutex %d fallido\n",MUTEX_UART_TX);
	}

	//Semáforo para RX de la UART
	if (sem_init(&(semaforo[SEMAPHORE_UART_RX]),0,0) != 0)
	{
		printf("\n[main] Inicialización de Semaphore %d fallido\n",SEMAPHORE_UART_RX);
	}

	//Semáforo para la activación y desactivación del TIMER
	if (sem_init(&(semaforo[SEMAPHORE_TIMER_ACTIVAR]),0,0) != 0)
	{
		printf("\nmain] Inicialización de Semaphore %d fallido\n",SEMAPHORE_TIMER_ACTIVAR);
	}

	//mutex para la variable contador del TIMER
	if (pthread_mutex_init(&(mutex[MUTEX_TIMER_CONTADOR]), NULL) != 0)
	{
		printf("\nmain] Inicialización de Mutex %d fallido\n",MUTEX_TIMER_CONTADOR);
	}

	//mutex para la variable mensajeGlobal
	if (pthread_mutex_init(&(mutex[MUTEX_VARIABLE_MENSAJEGLOBAL]), NULL) != 0)
	{
		printf("\nmain] Inicialización de Mutex %d fallido\n",MUTEX_VARIABLE_MENSAJEGLOBAL);
	}

	//mutex para la variable indicadora de timer activado
	if (pthread_mutex_init(&(mutex[MUTEX_VARIABLE_TIMERACTIVADO]), NULL) != 0)
	{
		printf("\nmain] Inicialización de Mutex %d fallido\n",MUTEX_VARIABLE_TIMERACTIVADO);
	}

	//*********************
	// Generación de Hilo *
	//*********************

	//Generación del hilo que se encarga de leer el socket y enviar la orden correspondiente por la UART
	errorHilo = pthread_create(&(idhilos[HILO_LEE_SOCKET]), NULL, &leeSocket, &hiloDatos);
	if(errorHilo != 0)
		printf("\n[main] Error al crear el hilo %d: [%s]\n",HILO_LEE_SOCKET,strerror(errorHilo));
	else
		printf("\n[main] Éxito al crear el hilo %d.\n",HILO_LEE_SOCKET);

	//Generación del hilo que se encarga de Monitorizar las variables y guardar los datos en la BBDD
	errorHilo = pthread_create(&(idhilos[HILO_LEE_UART]), NULL, &leeUART, &hiloDatos);
	if(errorHilo != 0)
		printf("\n[main] Error al crear el hilo %d: [%s]\n",HILO_LEE_UART,strerror(errorHilo));
	else
		printf("\n[main] Éxito al crear el hilo %d.\n",HILO_LEE_UART);

	//Generación del hilo que se encarga de Monitorizar las variables y guardar los datos en la BBDD
	errorHilo = pthread_create(&(idhilos[HILO_MONITOR_VAR]), NULL, &monitorizarVariables, &hiloDatos);
	if(errorHilo != 0)
		printf("\n[main] Error al crear el hilo %d: [%s]\n",HILO_MONITOR_VAR,strerror(errorHilo));
	else
		printf("\n[main] Éxito al crear el hilo %d.\n",HILO_MONITOR_VAR);

	//Generación del hilo TIMER
	errorHilo = pthread_create(&(idhilos[HILO_TIMER]), NULL, &controltimer, NULL);
	if(errorHilo != 0)
		printf("\n[main] Error al crear el hilo %d: [%s]\n",HILO_TIMER,strerror(errorHilo));
	else
		printf("\n[main] Éxito al crear el hilo %d.\n",HILO_TIMER);

	//Inicialización de últimos estados estados de los controladores

	iniciarControladoresLuz(descriptor_uart);
	iniciarSensoresLuz(descriptor_uart);

	while(1){
		sleep(120);
	}
}


/*************************
*   HILOS DEL PROGRAMA   *
*************************/

//Hilo que escucha el puerto del socket y recoge el mensaje.
void* leeSocket (void *arg){
	int  descriptor2;
	struct sockaddr_in cliente;
	int sockEntTam;
	int n;
	char buffer[256];
	char mensajeSolo[MAX_LONGITUD_MENSAJE];
	char mensaje[MAX_NUM_PARTES_MENSAJE+1][MAX_LONGITUD_MENSAJE];
	//Variables para registro en base de datos
	double valorVariable=0;
	int idVariable=0;
	int timestamp=0;
	int idZona=0;
	MYSQL* conector;
	int idControl;
	int valorSemaforoTimer=123;
	unsigned char estadoTimer = 0;
	char command[50];
	
	while(1){
		sockEntTam = sizeof(struct sockaddr_in);
		
		if((descriptor2 = accept(((manejadores *) arg)->socket, (struct sockaddr *) &cliente, &sockEntTam)) == -1){
			printf("\n[leeSocket] Error en la funcion accept.\n");
			exit(-1);
		}
		
		bzero(buffer, 256);
		n = read(descriptor2,buffer,255);
		if(n < 0){
			printf("\n[leeSocket] Error al leer del socket.\n");
		}

		sacarMensajeCabecera(buffer,mensajeSolo);

		fprintf(stdout,"\n[leeSocket] Valor mensajeSolo: %s\n",mensajeSolo);
		sacarDatosMensaje(mensajeSolo,mensaje);
		pthread_mutex_lock(&(mutex[MUTEX_VARIABLE_MENSAJEGLOBAL]));
		memcpy(mensajeGlobal, mensaje, sizeof(mensaje));
		pthread_mutex_unlock(&(mutex[MUTEX_VARIABLE_MENSAJEGLOBAL]));
		
		if(strcmp(mensaje[INDICE_ORDEN],ORDEN_FIJAR_ILUZ)==0){
			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensaje[INDICE_MENSAJE],((manejadores *) arg)->uart);
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado

			pthread_mutex_lock(&(mutex[MUTEX_VARIABLE_TIMERACTIVADO]));
			timerActivado = 1;
			pthread_mutex_unlock(&(mutex[MUTEX_VARIABLE_TIMERACTIVADO]));

			sem_getvalue(&(semaforo[SEMAPHORE_TIMER_ACTIVAR]),&valorSemaforoTimer);

			if(valorSemaforoTimer<=0){
				sem_post(&(semaforo[SEMAPHORE_TIMER_ACTIVAR]));
			}

			pthread_mutex_lock(&(mutex[MUTEX_TIMER_CONTADOR]));
			timerContador = 0;
			pthread_mutex_unlock(&(mutex[MUTEX_TIMER_CONTADOR]));
	
			n = write(descriptor2,"Recibido",8);
			if (n < 0){
				printf("\n[leeSocket] ERROR al escribir en el socket\n");
			}
		}
		//ORDENES DE ACTIVACIÓN Y DESACTIVACIÓN DE AUTOMATIZACIÓN DE CONTROL DE LA CASA
		else if(strcmp(mensaje[INDICE_ORDEN],ORDEN_AUTO_TEMPERATURA_APAGAR)==0 || strcmp(mensaje[INDICE_ORDEN],ORDEN_AUTO_TEMPERATURA_ENCENDER)==0 || strcmp(mensaje[INDICE_ORDEN],ORDEN_AUTO_LUZ_APAGAR)==0 || strcmp(mensaje[INDICE_ORDEN],ORDEN_AUTO_LUZ_ENCENDER)==0){
			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensaje[INDICE_MENSAJE],((manejadores *) arg)->uart);
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado

			if(strcmp(mensaje[INDICE_ORDEN],ORDEN_AUTO_TEMPERATURA_APAGAR)==0){
				idVariable = BBDD_ID_TIPOVAR_AUTOTEMP_ACTIVADO_USER;
				valorVariable=0;
			}
			else if(strcmp(mensaje[INDICE_ORDEN],ORDEN_AUTO_TEMPERATURA_ENCENDER)==0){
				idVariable = BBDD_ID_TIPOVAR_AUTOTEMP_ACTIVADO_USER;
				valorVariable=1;
			}
			else if(strcmp(mensaje[INDICE_ORDEN],ORDEN_AUTO_LUZ_APAGAR)==0){
				idVariable = BBDD_ID_TIPOVAR_AUTOLUZ_ACTIVADO_USER;
				valorVariable=0;
			}
			else if(strcmp(mensaje[INDICE_ORDEN],ORDEN_AUTO_LUZ_ENCENDER)==0){
				idVariable = BBDD_ID_TIPOVAR_AUTOLUZ_ACTIVADO_USER;
				valorVariable=1;
			}

			idControl=strtol(mensaje[INDICE_DIRECCION],NULL,16);
			timestamp=(int)time(NULL);
			
			conector=abrirBBDD(BBDD_HOST,BBDD_USER,BBDD_PASS,BBDD_NAME);
			idZona=sacarIdZona(conector,idControl);
			registrarDatoMonitor(conector,idVariable,valorVariable,timestamp,idZona);
			cerrarBBDD(conector);
	
			n = write(descriptor2,"Recibido",8);
			if (n < 0){
				printf("\n[leeSocket] ERROR al escribir en el socket\n");
			}
		}
		else if(strcmp(mensaje[INDICE_ORDEN],ORDEN_LUZ_ENCENDER)==0)
		{
			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensaje[INDICE_MENSAJE],((manejadores *) arg)->uart);
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado
	
			n = write(descriptor2,"Recibido",8);
			if (n < 0){
				printf("\n[leeSocket] ERROR al escribir en el socket\n");
			}
		}
		else if(strcmp(mensaje[INDICE_ORDEN],ORDEN_LUZ_APAGAR)==0)
		{
			pthread_mutex_lock(&(mutex[MUTEX_VARIABLE_TIMERACTIVADO]));
			estadoTimer = timerActivado;
			pthread_mutex_unlock(&(mutex[MUTEX_VARIABLE_TIMERACTIVADO]));

			if(estadoTimer!=0){
				pthread_mutex_lock(&(mutex[MUTEX_VARIABLE_TIMERACTIVADO]));
				timerActivado = 0;
				pthread_mutex_unlock(&(mutex[MUTEX_VARIABLE_TIMERACTIVADO]));

				pthread_mutex_lock(&(mutex[MUTEX_VARIABLE_MENSAJEGLOBAL]));
				idVariable = BBDD_ID_TIPOVAR_LUZACTION_INTENSIDAD_USER;

				idControl=strtol(mensajeGlobal[INDICE_DIRECCION],NULL,16);
				valorVariable=strtod(mensajeGlobal[INDICE_VALOR],NULL);
				pthread_mutex_unlock(&(mutex[MUTEX_VARIABLE_MENSAJEGLOBAL]));
				timestamp=(int)time(NULL);
				
				conector=abrirBBDD(BBDD_HOST,BBDD_USER,BBDD_PASS,BBDD_NAME);
				idZona=sacarIdZona(conector,idControl);
				registrarDatoMonitor(conector,idVariable,valorVariable,timestamp,idZona);
				cerrarBBDD(conector);
			}

			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensaje[INDICE_MENSAJE],((manejadores *) arg)->uart);
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado

			idVariable = BBDD_ID_TIPOVAR_LUZACTION_INTENSIDAD_USER;
			valorVariable = 0;

			idControl=strtol(mensaje[INDICE_DIRECCION],NULL,16);
			timestamp=(int)time(NULL);
			
			conector=abrirBBDD(BBDD_HOST,BBDD_USER,BBDD_PASS,BBDD_NAME);
			idZona=sacarIdZona(conector,idControl);
			registrarDatoMonitor(conector,idVariable,valorVariable,timestamp,idZona);
			cerrarBBDD(conector);
	
			n = write(descriptor2,"Recibido",8);
			if (n < 0){
				printf("\n[leeSocket] ERROR al escribir en el socket\n");
			}
		}
		else if(strcmp(mensaje[INDICE_ORDEN],ORDEN_TEMPERATURA_DESEADA_FIJAR)==0)
		{
			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensaje[INDICE_MENSAJE],((manejadores *) arg)->uart);
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado
	
			n = write(descriptor2,"Recibido",8);
			if (n < 0){
				printf("\n[leeSocket] ERROR al escribir en el socket\n");
			}
		}
		else if(strcmp(mensaje[INDICE_ORDEN],ORDEN_RESET_SISTEMA)==0 && strcmp(mensaje[INDICE_DIRECCION],DIRECCION_GLOBAL)==0)
		{
			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensaje[INDICE_MENSAJE],((manejadores *) arg)->uart);
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado
	
			n = write(descriptor2,"Recibido",8);
			if (n < 0){
				printf("\n[leeSocket] ERROR al escribir en el socket\n");
			}

			strcpy( command, REBOOT_SYSTEM );
			system(command);
		}
		
		close(descriptor2);

		printf("\n\n***********************************************************************************************************************\n\n");
	}
}

//Hilo que lee de la UART
void* leeUART(void *arg){
	while(1){
		sem_wait(&(semaforo[SEMAPHORE_UART_RX]));
		//----- CHECK FOR ANY RX BYTES -----
		if (((manejadores *) arg)->uart != -1)
		{
			// Read up to 255 characters from the port if they are there
			unsigned char rx_buffer[256];
			int rx_length = read(((manejadores *) arg)->uart, (void*)rx_buffer, 255);		//Filestream, buffer to store in, number of bytes to read (max)
			if (rx_length < 0)
			{
				//An error occured (will occur if there are no bytes)
			}
			else if (rx_length == 0)
			{
				//No data waiting
			}
			else
			{
				//Bytes received
				rx_buffer[rx_length] = '\0';
				strcpy(mensajeRX,rx_buffer);
				printf("\n[leeUART] %i bytes read : %s\n", rx_length, rx_buffer);
			}
		}
		sem_post(&(semaforo[SEMAPHORE_UART_RX]));
	}
}

//Hilo que pide periódicamente los datos a monitorizar al controlador correspondiente
void* monitorizarVariables (void *arg){
	double valorVariable=0;
	int idVariable=0;
	int timestamp=0;
	int idZona=0;
	int i;
	int indiceMensaje;
	char mensaje[MAX_NUM_PARTES_MENSAJE+1][MAX_LONGITUD_MENSAJE];
	char mensajeConsulta[MAX_NUM_PARTES_MENSAJE+1][MAX_LONGITUD_MENSAJE];
	MYSQL* conector;
	size_t longOrden=0;
	int idControl;

	fijarMensajesMonitor();

	while(1){
		sleep(PERIODO_MONITOR);

		indiceMensaje=0;
		while(strcmp(mensajesMonitor[indiceMensaje],"FIN")!=0){
			sacarDatosMensaje(mensajesMonitor[indiceMensaje],mensajeConsulta);

			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensajesMonitor[indiceMensaje],((manejadores *) arg)->uart);

			usleep(500000);
			sem_post(&(semaforo[SEMAPHORE_UART_RX]));
			usleep(100000);
			printf("[monitorizarVariables] Esperando contestación...\n");
			sem_wait(&(semaforo[SEMAPHORE_UART_RX]));
			fprintf(stdout,"[monitorizarVariables] Contestación recbida: -%s-\n",mensajeRX);

			if(strlen(mensajeRX)>0)
			{
				sacarDatosMensaje(mensajeRX,mensaje);
			}

			//printf("[monitorizarVariables] Valor de Orden de Mensaje de Consulta: %s\n[monitorizarVariables] Valor de Orden de Mensaje Recibido: %s",mensajeConsulta[INDICE_ORDEN],mensaje[INDICE_ORDEN]);

			i=0;
			while((strlen(mensajeRX)==0 || strcmp(mensaje[INDICE_ORDEN],mensajeConsulta[INDICE_ORDEN])!=0) && i<NUM_INTENTOS_CONSULTA){
				enviarDato(mensajesMonitor[indiceMensaje],((manejadores *) arg)->uart);
				usleep(500000);
				sem_post(&(semaforo[SEMAPHORE_UART_RX]));
				usleep(100000);
				printf("[monitorizarVariables] Esperando contestación...\n");
				sem_wait(&(semaforo[SEMAPHORE_UART_RX]));
				fprintf(stdout,"[monitorizarVariables] Contestación recbida: -%s-\n",mensajeRX);

				if(strlen(mensajeRX)>0)
				{
					sacarDatosMensaje(mensajeRX,mensaje);
				}
				i++;
			}
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado

			if(strlen(mensajeRX)==0){
				printf("\n[monitorizarVariables] Error al obtener la petición de valor de variable\n");
			}
			else{
				printf("\n[monitorizarVariables] Dato de valor de variable obtenido.\n");
				printf("\n[monitorizarVariables] Enviando mensaje respuesta a procesar...\n");
				sacarDatosMensaje(mensajeRX,mensaje);

				longOrden=strlen(mensaje[INDICE_ORDEN]);
				if(strncmp(mensaje[INDICE_ORDEN],PETICION_TEMP,longOrden)==0){
					idVariable = BBDD_ID_TIPOVAR_TEMPERATURA;
				}
				else if(strncmp(mensaje[INDICE_ORDEN],PETICION_ILUZ,longOrden)==0){
					idVariable = BBDD_ID_TIPOVAR_INTENSIDADLUZ;
				}

				idControl=strtol(mensaje[INDICE_DIRECCION],NULL,16);
				valorVariable=strtod(mensaje[INDICE_VALOR],NULL);
				timestamp=(int)time(NULL);
				
				conector=abrirBBDD(BBDD_HOST,BBDD_USER,BBDD_PASS,BBDD_NAME);
				idZona=sacarIdZona(conector,idControl);
				registrarDatoMonitor(conector,idVariable,valorVariable,timestamp,idZona);
				cerrarBBDD(conector);
			}
			indiceMensaje++;
		}

		printf("\n\n***********************************************************************************************************************\n\n");		
	}
}

//Hilo que simula un TIMER
void* controltimer (void *arg)
{
	int idControl;
	MYSQL* conector;
	int idVariable;
	double valorVariable;
	int timestamp;
	int idZona;

	while(1)
	{
		usleep(20000);

		pthread_mutex_lock(&(mutex[MUTEX_TIMER_CONTADOR]));
		timerContador++;

		if(timerContador >= 250)
		{
			timerContador = 0;

			if(timerActivado==1)
			{			
				pthread_mutex_lock(&(mutex[MUTEX_VARIABLE_MENSAJEGLOBAL]));
				idVariable = BBDD_ID_TIPOVAR_LUZACTION_INTENSIDAD_USER;

				idControl=strtol(mensajeGlobal[INDICE_DIRECCION],NULL,16);
				valorVariable=strtod(mensajeGlobal[INDICE_VALOR],NULL);
				pthread_mutex_unlock(&(mutex[MUTEX_VARIABLE_MENSAJEGLOBAL]));
				timestamp=(int)time(NULL);
				
				conector=abrirBBDD(BBDD_HOST,BBDD_USER,BBDD_PASS,BBDD_NAME);
				idZona=sacarIdZona(conector,idControl);
				registrarDatoMonitor(conector,idVariable,valorVariable,timestamp,idZona);
				cerrarBBDD(conector);

				pthread_mutex_lock(&(mutex[MUTEX_VARIABLE_TIMERACTIVADO]));
				timerActivado = 0;
				pthread_mutex_unlock(&(mutex[MUTEX_VARIABLE_TIMERACTIVADO]));
			}

			sem_wait(&(semaforo[SEMAPHORE_TIMER_ACTIVAR]));
		}
		pthread_mutex_unlock(&(mutex[MUTEX_TIMER_CONTADOR]));
	}
}

/*******************************************
*   FUNCIONES DE COMUNICACIÓN CON SOCKET   *
*******************************************/

//Función que crea un socket en el puerto definido por el parametro puerto.
socket_datos crearSocket(unsigned short puerto, int numconexiones){
	int descriptor1;
	struct sockaddr_in servidor;
	socket_datos maquina;
	
	maquina.error = 0;
	
	descriptor1 = socket(AF_INET,SOCK_STREAM,0);
	if(descriptor1 < 0){
		printf("\n[crearSocket] Error al crear el Socket (socket())\n");
		maquina.error = 1;
		return(maquina);
	}
	
	bzero((char *) &servidor, sizeof(servidor));	
	servidor.sin_family = AF_INET;
	servidor.sin_port = htons(puerto);
	servidor.sin_addr.s_addr = INADDR_ANY;
	
	if(bind(descriptor1,(struct sockaddr*) &servidor,sizeof(servidor)) == -1){
		printf("\n[crearSocket] Error al activar el Socket (bind()).\n");
		maquina.error=1;
		return(maquina);
	}

	if(listen(descriptor1,numconexiones) == -1){
		printf("\n[crearSocket] Error al establecer tarea de escucha en el Socket (listen())\n)");
		maquina.error = 1;
		return(maquina);
	}
	
	maquina.descriptor_socket = descriptor1;
	maquina.info_maquina = servidor;
	
	return(maquina);
}


/*****************************************
*   FUNCIONES DE COMUNICACIÓN CON UART   *
*****************************************/

//Función que abre la UART0 (Por el momento parece que ttyAMA0 solo dispone el RbP de él).
int abrir_uart(){
	int descriptor_uart0 = -1;
	struct termios opcionesUART;
	
	descriptor_uart0 = open("/dev/ttyAMA0", O_RDWR | O_NOCTTY | O_NDELAY);
	if (descriptor_uart0 == -1)
	{
		printf("\n[abrir_uart] Error al abrir la UART, compruebe que no está siendo utilizada por otra aplicación.\n");
		return(-1);
	}
	tcgetattr(descriptor_uart0, &opcionesUART);
	opcionesUART.c_cflag = B9600 | CS8 | CLOCAL | CREAD;
	opcionesUART.c_iflag = IGNPAR | ICRNL;
	opcionesUART.c_oflag = 0;
	opcionesUART.c_lflag = 0;
	tcflush(descriptor_uart0, TCIFLUSH);
	tcsetattr(descriptor_uart0, TCSANOW, &opcionesUART);
	
	usleep(10000);
	
	return(descriptor_uart0);
}

//Función que envía el dato datoOut[] por la UART 
int enviarDato (char datoOut[],int uart){
	unsigned char tx_buffer[20];
	unsigned char *p_tx_buffer;
	int respuesta = 2;
	int i;
	int numCaractEnviados;

	fprintf(stdout,"\n[enviarDato] Enviando dato -%s-...\n",datoOut);

	p_tx_buffer = &tx_buffer[0];
	
	i=0;
	while(datoOut[i] != '\0'){
		*p_tx_buffer++ = datoOut[i];
		i++;
	}
	//*p_tx_buffer++ = '\n';
	if (uart != -1)
	{
		respuesta=1;
		numCaractEnviados = write(uart, &tx_buffer[0], (p_tx_buffer - &tx_buffer[0]));

		if (numCaractEnviados < 0)
		{
			printf("\n[enviarDato] UART TX error\n");
			respuesta = 0;
		}
	}
	printf("\n[enviarDato] Fin de envio de dato.\n");

	return (respuesta);
}


/*****************************************
*   FUNCIONES DE COMUNICACIÓN CON BBDD   *
*****************************************/

void cerrarBBDD (MYSQL* descriptorBBDD){
	printf("\n[cerrarBBDD] Cerrando BBDD...\n");
	mysql_close(descriptorBBDD);
	printf("\n[cerrarBBDD] BBDD cerrada\n");
}

MYSQL* abrirBBDD (char maquina[],char usuario[],char password[],char baseDatos[]){
	MYSQL *con;

	printf("\n[abrirBBDD] Abriendo BBDD...\n");

	con = mysql_init(NULL);

	if (con == NULL){
		fprintf(stderr, "%s\n", mysql_error(con));
		return(con);
	}
	else{
		if (mysql_real_connect(con, maquina, usuario, password, baseDatos, 0, NULL, 0) == NULL){
			fprintf(stderr, "%s\n", mysql_error(con));
			mysql_close(con);
			return(con);
		}
		else{
			printf("\n[abrirBBDD] BBDD abierta\n");
			return(con);
		}
	}
}

int registrarDatoMonitor(MYSQL* conect, int idvariable, double valor, int timestamp, int idzona){
	char query[255];

	printf("\n[registrarDatoMonitor] Registrando dato en BBDD...\n");

	snprintf(query,sizeof(query),"INSERT INTO registrovars (idvariable,valor,idzona) VALUES(%d,%lf,%d);",idvariable,valor,idzona);

	fprintf(stdout,"\n[registrarDatoMonitor] Query: %s\n",query);

	if (mysql_query(conect, query)) 
	{
		fprintf(stderr, "\n[registrarDatoMonitor] %s\n", mysql_error(conect));
		mysql_close(conect);
		return(0);
	}
	else
	{
		printf("\n[registrarDatoMonitor] Dato registrado en BBDD\n");
		return(1);
	}
}
int sacarIdZona(MYSQL* conect,int idControlador){
	char query[255];
	MYSQL_RES *result;
	int num_fields=0;
	MYSQL_ROW row;
	int i;
	int idZona;
	int resultadoQuery;

	sprintf(query,"SELECT id FROM zonas WHERE idcontrolador=%d;",idControlador);

	printf("\n[sacarIdZona] Query: %s\n",query);

	resultadoQuery = mysql_query(conect, query);

	if (resultadoQuery != 0)
	{
		printf("\n[sacarIdZona] Error al consultar con Query (Error: %d)\n",resultadoQuery);
	}

	result = mysql_store_result(conect);

	if (result == NULL) 
	{
		printf("\n[sacarIdZona] Error al sacar los datos de la consulta\n");
	}

	num_fields = mysql_num_fields(result);

	while ((row = mysql_fetch_row(result))) 
	{ 
		for(i = 0; i < num_fields; i++) 
		{
			idZona=atoi(row[i]); 
		} 
	}

	mysql_free_result(result);
	return(idZona);
}
int tomarUltimoValorIntensidadLuzFijada(MYSQL* conect,int idControlador){
	char query[255];
	MYSQL_RES *result;
	int iLuz=0;
	int num_fields=0;
	MYSQL_ROW row;
	int resultadoQuery;
	int i=0;

	sprintf(query,"SELECT valor FROM registrovars WHERE idzona=%d AND idvariable=%d ORDER BY fecha DESC LIMIT 1;",idControlador,BBDD_ID_TIPOVAR_LUZACTION_INTENSIDAD_USER);

	printf("\n[tomarUltimoValorIntensidadLuzFijada] Query: %s\n",query);

	resultadoQuery = mysql_query(conect, query);

	if (resultadoQuery != 0)
	{
		printf("\n[tomarUltimoValorIntensidadLuzFijada] Error al consultar con Query (Error: %d)\n",resultadoQuery);
	}

	result = mysql_store_result(conect);

	if (result == NULL) 
	{
		printf("\n[tomarUltimoValorIntensidadLuzFijada] Error al sacar los datos de la consulta\n");
	}

	num_fields = mysql_num_fields(result);

	while ((row = mysql_fetch_row(result))) 
	{ 
		for(i = 0; i < num_fields; i++) 
		{
			iLuz=atoi(row[i]); 
		} 
	}

	return iLuz;
}

int tomarUltimoValorAutoLuz(MYSQL* conect,int idControlador){
	char query[255];
	MYSQL_RES *result;
	int autoLuz=0;
	int num_fields=0;
	MYSQL_ROW row;
	int resultadoQuery;
	int i=0;

	sprintf(query,"SELECT valor FROM registrovars WHERE idzona=%d AND idvariable=%d ORDER BY fecha DESC LIMIT 1;",idControlador,BBDD_ID_TIPOVAR_AUTOLUZ_ACTIVADO_USER);

	printf("\n[tomarUltimoValorAutoLuz] Query: %s\n",query);

	resultadoQuery = mysql_query(conect, query);

	if (resultadoQuery != 0)
	{
		printf("\n[tomarUltimoValorAutoLuz] Error al consultar con Query (Error: %d)\n",resultadoQuery);
	}

	result = mysql_store_result(conect);

	if (result == NULL) 
	{
		printf("\n[tomarUltimoValorAutoLuz] Error al sacar los datos de la consulta\n");
	}

	num_fields = mysql_num_fields(result);

	while ((row = mysql_fetch_row(result))) 
	{ 
		for(i = 0; i < num_fields; i++) 
		{
			autoLuz=atoi(row[i]); 
		} 
	}

	return autoLuz;
}

void tomarControladoresLuz(MYSQL* conect,int direcciones[])
{
	char query[255];
	int i=0;
	int j=0;
	char funcionalidad[255];
	int resultadoQuery;
	MYSQL_RES *result;
	int num_fields=0;
	MYSQL_ROW row;

	i=0;
	for(i=0;i<50;i++)
	{
		j=0;
		for(j=0;j<10;j++){
			direcciones[i]=0;
		}
	}

	sprintf(query,"SELECT idcontrolador, funcionalidad FROM zonas;");

	printf("\n[tomarControladoresLuz] Query: %s\n",query);

	resultadoQuery = mysql_query(conect, query);

	if (resultadoQuery != 0)
	{
		printf("\n[tomarControladoresLuz] Error al consultar con Query (Error: %d)\n",resultadoQuery);
	}

	result = mysql_store_result(conect);

	if (result == NULL) 
	{
		printf("\n[tomarControladoresLuz] Error al sacar los datos de la consulta\n");
	}

	num_fields = mysql_num_fields(result);

	i=0;
	while ((row = mysql_fetch_row(result))) 
	{
		sprintf(funcionalidad,"%s\0",row[1]);

		if(strstr(funcionalidad,CONTROL_LUZ)!=NULL)
		{
			direcciones[i]=atoi(row[0]);
			i++;
		}
	}
}

void tomarSensoresLuz(MYSQL* conect,int direcciones[])
{
	char query[255];
	int i=0;
	int j=0;
	char funcionalidad[255];
	int resultadoQuery;
	MYSQL_RES *result;
	int num_fields=0;
	MYSQL_ROW row;

	i=0;
	for(i=0;i<50;i++)
	{
		j=0;
		for(j=0;j<10;j++){
			direcciones[i]=0;
		}
	}

	sprintf(query,"SELECT idcontrolador, funcionalidad FROM zonas;");

	printf("\n[tomarControladoresLuz] Query: %s\n",query);

	resultadoQuery = mysql_query(conect, query);

	if (resultadoQuery != 0)
	{
		printf("\n[tomarControladoresLuz] Error al consultar con Query (Error: %d)\n",resultadoQuery);
	}

	result = mysql_store_result(conect);

	if (result == NULL) 
	{
		printf("\n[tomarControladoresLuz] Error al sacar los datos de la consulta\n");
	}

	num_fields = mysql_num_fields(result);

	i=0;
	while ((row = mysql_fetch_row(result))) 
	{
		sprintf(funcionalidad,"%s\0",row[1]);

		if(strstr(funcionalidad,SENSOR_LUZ)!=NULL)
		{
			direcciones[i]=atoi(row[0]);
			i++;
		}
	}
}

/***************************************
*   FUNCIONES DE CONTROL DE MENSAJES   *
***************************************/

void sacarDatosMensaje(char buffer[],char datosMensaje[MAX_NUM_PARTES_MENSAJE+1][MAX_LONGITUD_MENSAJE]){
	int contadorBuffer=0;
	int contadorMensaje=0;
	int contadorPartes=0;
	int parteMensaje=1;

	fprintf(stdout,"\n[sacarDatosMensaje] Procesando datos de mensaje -%s-...\n",buffer);

	while(buffer[contadorBuffer] != ' ' && buffer[contadorBuffer] != 10 && buffer[contadorBuffer] != 13){
		//fprintf(stdout,"\nCopiando caracter '%c' a variable 'mensaje'",buffer[contadorBuffer]);
		datosMensaje[INDICE_MENSAJE][contadorMensaje] = buffer[contadorBuffer];
		//fprintf(stdout,"\nCaracter '%c' copiado a variable 'mensaje'",buffer[contadorBuffer]);

		if(buffer[contadorBuffer]!='_'){
			datosMensaje[parteMensaje][contadorPartes] = buffer[contadorBuffer];
			contadorPartes++;
		}
		else{
			datosMensaje[parteMensaje][contadorPartes] = '\0';
			contadorPartes=0;
			parteMensaje++;
		}
		contadorBuffer++;
		contadorMensaje++;
	}
	datosMensaje[parteMensaje][contadorPartes] = '\0';
	datosMensaje[INDICE_MENSAJE][contadorMensaje] = '\r';
	contadorMensaje++;
	datosMensaje[INDICE_MENSAJE][contadorMensaje] = '\0';

	printf("\n\n\tMensaje: %s",datosMensaje[INDICE_MENSAJE]);
	printf("\n\tDireccion: %s",datosMensaje[INDICE_DIRECCION]);
	printf("\n\tValor: %s",datosMensaje[INDICE_VALOR]);
	printf("\n\tOrden: %s\n\n",datosMensaje[INDICE_ORDEN]);

	printf("\n[sacarDatosMensaje] Datos de mensaje procesados\n");
}

void sacarMensajeCabecera(char buffer[],char mensaje[]){
	int contadorBuffer=14;
	int contador=0;

	while(buffer[contadorBuffer]!=' '){
		mensaje[contador]=buffer[contadorBuffer];

		contadorBuffer++;
		contador++;
	}
	mensaje[contador]='\r';
	contador++;
	mensaje[contador]='\0';
}

void fijarMensajesMonitor(void){
	sprintf(mensajesMonitor[0],"%s_%s\r",DIRECCION_SENSOR_SALON,PETICION_TEMP);
	sprintf(mensajesMonitor[1],"%s_%s\r",DIRECCION_SENSOR_SALON,PETICION_ILUZ);

	strcpy(mensajesMonitor[2],"FIN\0");
}

/***************************************************
*   FUNCIONES DE INICIALIZACIÓN DE CONTROLADORES   *
***************************************************/


void iniciarControladoresLuz(int uart)
{
	int valorLuz=0;
	int i=0;
	MYSQL* conector;
	int idZona;
	char mensajeInicio[20];
	int controladores[50];

	conector=abrirBBDD(BBDD_HOST,BBDD_USER,BBDD_PASS,BBDD_NAME);
	tomarControladoresLuz(conector,controladores);

	i=0;
	while(controladores[i]>0){
		idZona=sacarIdZona(conector,controladores[i]);
		valorLuz=tomarUltimoValorIntensidadLuzFijada(conector,idZona);

		if(valorLuz>0)
		{
			sprintf(mensajeInicio,"%X_%s\r",controladores[i],ORDEN_LUZ_ENCENDER);

			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensajeInicio,uart);
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado

			usleep(10000);

			sprintf(mensajeInicio,"%X_%s_%d\r",controladores[i],ORDEN_FIJAR_ILUZ ,valorLuz);

			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensajeInicio,uart);
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado

			usleep(10000);
		}
		i++;
	}
	cerrarBBDD(conector);
}

void iniciarSensoresLuz(int uart)
{
	int valorAutoLuz=0;
	int i=0;
	MYSQL* conector;
	int idZona;
	char mensajeInicio[20];
	int controladores[50];

	conector=abrirBBDD(BBDD_HOST,BBDD_USER,BBDD_PASS,BBDD_NAME);
	tomarSensoresLuz(conector,controladores);

	i=0;
	while(controladores[i]>0){
		idZona=sacarIdZona(conector,controladores[i]);
		valorAutoLuz=tomarUltimoValorAutoLuz(conector,idZona);

		if(valorAutoLuz==1)
		{
			sprintf(mensajeInicio,"%X_%s\r",controladores[i],ORDEN_AUTO_LUZ_ENCENDER);

			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensajeInicio,uart);
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado

			usleep(10000);
		}
		else{
			sprintf(mensajeInicio,"%X_%s\r",controladores[i],ORDEN_AUTO_LUZ_APAGAR);

			pthread_mutex_lock(&(mutex[MUTEX_UART_TX])); //Mutex de UART bloqueado
			enviarDato(mensajeInicio,uart);
			pthread_mutex_unlock(&(mutex[MUTEX_UART_TX])); //Mutex de UART desbloqueado

			usleep(10000);
		}
		i++;
	}
	cerrarBBDD(conector);
}

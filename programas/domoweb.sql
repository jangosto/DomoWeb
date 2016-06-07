-- phpMyAdmin SQL Dump
-- version 4.0.6deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 06, 2014 at 06:12 PM
-- Server version: 5.5.35-0ubuntu0.13.10.2
-- PHP Version: 5.5.3-1ubuntu2.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `domoweb`
--

-- --------------------------------------------------------

--
-- Table structure for table `registrovars`
--

CREATE TABLE IF NOT EXISTS `registrovars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador del valor registrado',
  `idvariable` smallint(5) unsigned NOT NULL COMMENT 'Identificador del tipo de variable del valor registrado',
  `valor` decimal(15,5) NOT NULL COMMENT 'Valor registrado',
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha del registro del valor',
  `idzona` smallint(5) unsigned NOT NULL COMMENT 'Identificador de la zona monitorizada',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=ascii COLLATE=ascii_bin COMMENT='Tabla de valores registrados' ;


-- --------------------------------------------------------

--
-- Table structure for table `variables`
--

CREATE TABLE IF NOT EXISTS `variables` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Id de tipo de variable',
  `nombre` varchar(50) COLLATE utf8_bin NOT NULL COMMENT 'Nombre de la variable',
  `descripcion` varchar(250) COLLATE utf8_bin NOT NULL COMMENT 'Descripción de la variable',
  `unidad` varchar(50) COLLATE utf8_bin NOT NULL COMMENT 'Nombre de la unidad de la variable',
  `simbolounidad` varchar(10) COLLATE utf8_bin NOT NULL COMMENT 'Símbolo de la unidad de la variable',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tabla de tipos de variable registradas' ;

-- --------------------------------------------------------

--
-- Table structure for table `zonas`
--

CREATE TABLE IF NOT EXISTS `zonas` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador de zona',
  `nombre` varchar(50) COLLATE utf8_bin NOT NULL COMMENT 'Nombre de la zona',
  `descripcion` varchar(250) COLLATE utf8_bin NOT NULL COMMENT 'Descripción de la zona',
  `idcontrolador` smallint(5) unsigned NOT NULL COMMENT 'Identificador del controlador correspondiente a la zona',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idcontrolador` (`idcontrolador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tabla de zonas monitorizadas' ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

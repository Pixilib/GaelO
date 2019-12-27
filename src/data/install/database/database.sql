-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 27, 2019 at 11:36 PM
-- Server version: 5.7.28-0ubuntu0.16.04.2
-- PHP Version: 7.3.11-1+ubuntu16.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lysarc2`
--

-- --------------------------------------------------------

--
-- Table structure for table `affiliated_centers`
--

CREATE TABLE `affiliated_centers` (
  `username` varchar(32) NOT NULL,
  `center` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `centers`
--

CREATE TABLE `centers` (
  `code` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `country_code` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `centers`
--

INSERT INTO `centers` (`code`, `name`, `country_code`) VALUES
(0, 'default', 'FR');

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

CREATE TABLE `country` (
  `country_code` varchar(5) NOT NULL,
  `country_us` varchar(100) NOT NULL,
  `country_fr` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `country`
--

INSERT INTO `country` (`country_code`, `country_us`, `country_fr`) VALUES
('AC', 'Ascension Island', 'Île de l’Ascension'),
('AD', 'Andorra', 'Andorre'),
('AE', 'United Arab Emirates', 'Émirats arabes unis'),
('AF', 'Afghanistan', 'Afghanistan'),
('AG', 'Antigua & Barbuda', 'Antigua-et-Barbuda'),
('AI', 'Anguilla', 'Anguilla'),
('AL', 'Albania', 'Albanie'),
('AM', 'Armenia', 'Arménie'),
('AO', 'Angola', 'Angola'),
('AQ', 'Antarctica', 'Antarctique'),
('AR', 'Argentina', 'Argentine'),
('AS', 'American Samoa', 'Samoa américaines'),
('AT', 'Austria', 'Autriche'),
('AU', 'Australia', 'Australie'),
('AW', 'Aruba', 'Aruba'),
('AX', 'Åland Islands', 'Îles Åland'),
('AZ', 'Azerbaijan', 'Azerbaïdjan'),
('BA', 'Bosnia & Herzegovina', 'Bosnie-Herzégovine'),
('BB', 'Barbados', 'Barbade'),
('BD', 'Bangladesh', 'Bangladesh'),
('BE', 'Belgium', 'Belgique'),
('BF', 'Burkina Faso', 'Burkina Faso'),
('BG', 'Bulgaria', 'Bulgarie'),
('BH', 'Bahrain', 'Bahreïn'),
('BI', 'Burundi', 'Burundi'),
('BJ', 'Benin', 'Bénin'),
('BL', 'St. Barthélemy', 'Saint-Barthélemy'),
('BM', 'Bermuda', 'Bermudes'),
('BN', 'Brunei', 'Brunéi Darussalam'),
('BO', 'Bolivia', 'Bolivie'),
('BQ', 'Caribbean Netherlands', 'Pays-Bas caribéens'),
('BR', 'Brazil', 'Brésil'),
('BS', 'Bahamas', 'Bahamas'),
('BT', 'Bhutan', 'Bhoutan'),
('BW', 'Botswana', 'Botswana'),
('BY', 'Belarus', 'Biélorussie'),
('BZ', 'Belize', 'Belize'),
('CA', 'Canada', 'Canada'),
('CC', 'Cocos (Keeling) Islands', 'Îles Cocos'),
('CD', 'Congo - Kinshasa', 'Congo-Kinshasa'),
('CF', 'Central African Republic', 'République centrafricaine'),
('CG', 'Congo - Brazzaville', 'Congo-Brazzaville'),
('CH', 'Switzerland', 'Suisse'),
('CI', 'Côte d’Ivoire', 'Côte d’Ivoire'),
('CK', 'Cook Islands', 'Îles Cook'),
('CL', 'Chile', 'Chili'),
('CM', 'Cameroon', 'Cameroun'),
('CN', 'China', 'Chine'),
('CO', 'Colombia', 'Colombie'),
('CR', 'Costa Rica', 'Costa Rica'),
('CU', 'Cuba', 'Cuba'),
('CV', 'Cape Verde', 'Cap-Vert'),
('CW', 'Curaçao', 'Curaçao'),
('CX', 'Christmas Island', 'Île Christmas'),
('CY', 'Cyprus', 'Chypre'),
('CZ', 'Czechia', 'Tchéquie'),
('DE', 'Germany', 'Allemagne'),
('DG', 'Diego Garcia', 'Diego Garcia'),
('DJ', 'Djibouti', 'Djibouti'),
('DK', 'Denmark', 'Danemark'),
('DM', 'Dominica', 'Dominique'),
('DO', 'Dominican Republic', 'République dominicaine'),
('DZ', 'Algeria', 'Algérie'),
('EA', 'Ceuta & Melilla', 'Ceuta et Melilla'),
('EC', 'Ecuador', 'Équateur'),
('EE', 'Estonia', 'Estonie'),
('EG', 'Egypt', 'Égypte'),
('EH', 'Western Sahara', 'Sahara occidental'),
('ER', 'Eritrea', 'Érythrée'),
('ES', 'Spain', 'Espagne'),
('ET', 'Ethiopia', 'Éthiopie'),
('FI', 'Finland', 'Finlande'),
('FJ', 'Fiji', 'Fidji'),
('FK', 'Falkland Islands', 'Îles Malouines'),
('FM', 'Micronesia', 'États fédérés de Micronésie'),
('FO', 'Faroe Islands', 'Îles Féroé'),
('FR', 'France', 'France'),
('GA', 'Gabon', 'Gabon'),
('GB', 'United Kingdom', 'Royaume-Uni'),
('GD', 'Grenada', 'Grenade'),
('GE', 'Georgia', 'Géorgie'),
('GF', 'French Guiana', 'Guyane française'),
('GG', 'Guernsey', 'Guernesey'),
('GH', 'Ghana', 'Ghana'),
('GI', 'Gibraltar', 'Gibraltar'),
('GL', 'Greenland', 'Groenland'),
('GM', 'Gambia', 'Gambie'),
('GN', 'Guinea', 'Guinée'),
('GP', 'Guadeloupe', 'Guadeloupe'),
('GQ', 'Equatorial Guinea', 'Guinée équatoriale'),
('GR', 'Greece', 'Grèce'),
('GS', 'South Georgia & South Sandwich Islands', 'Géorgie du Sud et îles Sandwich du Sud'),
('GT', 'Guatemala', 'Guatemala'),
('GU', 'Guam', 'Guam'),
('GW', 'Guinea-Bissau', 'Guinée-Bissau'),
('GY', 'Guyana', 'Guyana'),
('HK', 'Hong Kong SAR China', 'R.A.S. chinoise de Hong Kong'),
('HN', 'Honduras', 'Honduras'),
('HR', 'Croatia', 'Croatie'),
('HT', 'Haiti', 'Haïti'),
('HU', 'Hungary', 'Hongrie'),
('IC', 'Canary Islands', 'Îles Canaries'),
('ID', 'Indonesia', 'Indonésie'),
('IE', 'Ireland', 'Irlande'),
('IL', 'Israel', 'Israël'),
('IM', 'Isle of Man', 'Île de Man'),
('IN', 'India', 'Inde'),
('IO', 'British Indian Ocean Territory', 'Territoire britannique de l’océan Indien'),
('IQ', 'Iraq', 'Irak'),
('IR', 'Iran', 'Iran'),
('IS', 'Iceland', 'Islande'),
('IT', 'Italy', 'Italie'),
('JE', 'Jersey', 'Jersey'),
('JM', 'Jamaica', 'Jamaïque'),
('JO', 'Jordan', 'Jordanie'),
('JP', 'Japan', 'Japon'),
('KE', 'Kenya', 'Kenya'),
('KG', 'Kyrgyzstan', 'Kirghizistan'),
('KH', 'Cambodia', 'Cambodge'),
('KI', 'Kiribati', 'Kiribati'),
('KM', 'Comoros', 'Comores'),
('KN', 'St. Kitts & Nevis', 'Saint-Christophe-et-Niévès'),
('KP', 'North Korea', 'Corée du Nord'),
('KR', 'South Korea', 'Corée du Sud'),
('KW', 'Kuwait', 'Koweït'),
('KY', 'Cayman Islands', 'Îles Caïmans'),
('KZ', 'Kazakhstan', 'Kazakhstan'),
('LA', 'Laos', 'Laos'),
('LB', 'Lebanon', 'Liban'),
('LC', 'St. Lucia', 'Sainte-Lucie'),
('LI', 'Liechtenstein', 'Liechtenstein'),
('LK', 'Sri Lanka', 'Sri Lanka'),
('LR', 'Liberia', 'Libéria'),
('LS', 'Lesotho', 'Lesotho'),
('LT', 'Lithuania', 'Lituanie'),
('LU', 'Luxembourg', 'Luxembourg'),
('LV', 'Latvia', 'Lettonie'),
('LY', 'Libya', 'Libye'),
('MA', 'Morocco', 'Maroc'),
('MC', 'Monaco', 'Monaco'),
('MD', 'Moldova', 'Moldavie'),
('ME', 'Montenegro', 'Monténégro'),
('MF', 'St. Martin', 'Saint-Martin'),
('MG', 'Madagascar', 'Madagascar'),
('MH', 'Marshall Islands', 'Îles Marshall'),
('MK', 'North Macedonia', 'Macédoine'),
('ML', 'Mali', 'Mali'),
('MM', 'Myanmar (Burma)', 'Myanmar (Birmanie)'),
('MN', 'Mongolia', 'Mongolie'),
('MO', 'Macao SAR China', 'R.A.S. chinoise de Macao'),
('MP', 'Northern Mariana Islands', 'Îles Mariannes du Nord'),
('MQ', 'Martinique', 'Martinique'),
('MR', 'Mauritania', 'Mauritanie'),
('MS', 'Montserrat', 'Montserrat'),
('MT', 'Malta', 'Malte'),
('MU', 'Mauritius', 'Maurice'),
('MV', 'Maldives', 'Maldives'),
('MW', 'Malawi', 'Malawi'),
('MX', 'Mexico', 'Mexique'),
('MY', 'Malaysia', 'Malaisie'),
('MZ', 'Mozambique', 'Mozambique'),
('NA', 'Namibia', 'Namibie'),
('NC', 'New Caledonia', 'Nouvelle-Calédonie'),
('NE', 'Niger', 'Niger'),
('NF', 'Norfolk Island', 'Île Norfolk'),
('NG', 'Nigeria', 'Nigéria'),
('NI', 'Nicaragua', 'Nicaragua'),
('NL', 'Netherlands', 'Pays-Bas'),
('NO', 'Norway', 'Norvège'),
('NP', 'Nepal', 'Népal'),
('NR', 'Nauru', 'Nauru'),
('NU', 'Niue', 'Niue'),
('NZ', 'New Zealand', 'Nouvelle-Zélande'),
('OM', 'Oman', 'Oman'),
('PA', 'Panama', 'Panama'),
('PE', 'Peru', 'Pérou'),
('PF', 'French Polynesia', 'Polynésie française'),
('PG', 'Papua New Guinea', 'Papouasie-Nouvelle-Guinée'),
('PH', 'Philippines', 'Philippines'),
('PK', 'Pakistan', 'Pakistan'),
('PL', 'Poland', 'Pologne'),
('PM', 'St. Pierre & Miquelon', 'Saint-Pierre-et-Miquelon'),
('PN', 'Pitcairn Islands', 'Îles Pitcairn'),
('PR', 'Puerto Rico', 'Porto Rico'),
('PS', 'Palestinian Territories', 'Territoires palestiniens'),
('PT', 'Portugal', 'Portugal'),
('PW', 'Palau', 'Palaos'),
('PY', 'Paraguay', 'Paraguay'),
('QA', 'Qatar', 'Qatar'),
('RE', 'Réunion', 'La Réunion'),
('RO', 'Romania', 'Roumanie'),
('RS', 'Serbia', 'Serbie'),
('RU', 'Russia', 'Russie'),
('RW', 'Rwanda', 'Rwanda'),
('SA', 'Saudi Arabia', 'Arabie saoudite'),
('SB', 'Solomon Islands', 'Îles Salomon'),
('SC', 'Seychelles', 'Seychelles'),
('SD', 'Sudan', 'Soudan'),
('SE', 'Sweden', 'Suède'),
('SG', 'Singapore', 'Singapour'),
('SH', 'St. Helena', 'Sainte-Hélène'),
('SI', 'Slovenia', 'Slovénie'),
('SJ', 'Svalbard & Jan Mayen', 'Svalbard et Jan Mayen'),
('SK', 'Slovakia', 'Slovaquie'),
('SL', 'Sierra Leone', 'Sierra Leone'),
('SM', 'San Marino', 'Saint-Marin'),
('SN', 'Senegal', 'Sénégal'),
('SO', 'Somalia', 'Somalie'),
('SR', 'Suriname', 'Suriname'),
('SS', 'South Sudan', 'Soudan du Sud'),
('ST', 'São Tomé & Príncipe', 'Sao Tomé-et-Principe'),
('SV', 'El Salvador', 'Salvador'),
('SX', 'Sint Maarten', 'Saint-Martin (partie néerlandaise)'),
('SY', 'Syria', 'Syrie'),
('SZ', 'Eswatini', 'Eswatini'),
('TA', 'Tristan da Cunha', 'Tristan da Cunha'),
('TC', 'Turks & Caicos Islands', 'Îles Turques-et-Caïques'),
('TD', 'Chad', 'Tchad'),
('TF', 'French Southern Territories', 'Terres australes françaises'),
('TG', 'Togo', 'Togo'),
('TH', 'Thailand', 'Thaïlande'),
('TJ', 'Tajikistan', 'Tadjikistan'),
('TK', 'Tokelau', 'Tokelau'),
('TL', 'Timor-Leste', 'Timor oriental'),
('TM', 'Turkmenistan', 'Turkménistan'),
('TN', 'Tunisia', 'Tunisie'),
('TO', 'Tonga', 'Tonga'),
('TR', 'Turkey', 'Turquie'),
('TT', 'Trinidad & Tobago', 'Trinité-et-Tobago'),
('TV', 'Tuvalu', 'Tuvalu'),
('TW', 'Taiwan', 'Taïwan'),
('TZ', 'Tanzania', 'Tanzanie'),
('UA', 'Ukraine', 'Ukraine'),
('UG', 'Uganda', 'Ouganda'),
('UM', 'U.S. Outlying Islands', 'Îles mineures éloignées des États-Unis'),
('US', 'United States', 'États-Unis'),
('UY', 'Uruguay', 'Uruguay'),
('UZ', 'Uzbekistan', 'Ouzbékistan'),
('VA', 'Vatican City', 'État de la Cité du Vatican'),
('VC', 'St. Vincent & Grenadines', 'Saint-Vincent-et-les-Grenadines'),
('VE', 'Venezuela', 'Venezuela'),
('VG', 'British Virgin Islands', 'Îles Vierges britanniques'),
('VI', 'U.S. Virgin Islands', 'Îles Vierges des États-Unis'),
('VN', 'Vietnam', 'Vietnam'),
('VU', 'Vanuatu', 'Vanuatu'),
('WF', 'Wallis & Futuna', 'Wallis-et-Futuna'),
('WS', 'Samoa', 'Samoa'),
('XA', 'Pseudo-Accents', 'pseudo-accents'),
('XB', 'Pseudo-Bidi', 'pseudo-bidi'),
('XK', 'Kosovo', 'Kosovo'),
('YE', 'Yemen', 'Yémen'),
('YT', 'Mayotte', 'Mayotte'),
('ZA', 'South Africa', 'Afrique du Sud'),
('ZM', 'Zambia', 'Zambie'),
('ZW', 'Zimbabwe', 'Zimbabwe');

-- --------------------------------------------------------

--
-- Table structure for table `documentation`
--

CREATE TABLE `documentation` (
  `id_documentation` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `document_date` date NOT NULL,
  `study` varchar(32) NOT NULL,
  `version` varchar(10) NOT NULL,
  `investigator` tinyint(1) NOT NULL DEFAULT '0',
  `controller` tinyint(1) NOT NULL DEFAULT '0',
  `monitor` tinyint(1) NOT NULL DEFAULT '0',
  `reviewer` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `job`
--

CREATE TABLE `job` (
  `name` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `job`
--

INSERT INTO `job` (`name`) VALUES
('CRA'),
('Monitor'),
('Nuclearist'),
('PI'),
('Radiologist'),
('Study nurse'),
('Supervision');

-- --------------------------------------------------------

--
-- Table structure for table `orthanc_series`
--

CREATE TABLE `orthanc_series` (
  `study_orthanc_id` varchar(44) NOT NULL,
  `modality` tinytext,
  `acquisition_date` tinytext,
  `acquisition_time` tinytext,
  `acquisition_datetime` datetime DEFAULT NULL,
  `series_description` tinytext,
  `injected_dose` bigint(13) DEFAULT NULL,
  `radiopharmaceutical` tinytext,
  `half_life` bigint(13) DEFAULT NULL,
  `injected_datetime` datetime DEFAULT NULL,
  `injected_time` tinytext,
  `injected_activity` bigint(13) DEFAULT NULL,
  `patient_weight` int(11) DEFAULT NULL,
  `series_orthanc_id` varchar(44) NOT NULL,
  `number_of_instances` int(11) NOT NULL,
  `serie_uid` tinytext NOT NULL,
  `serie_number` tinytext,
  `serie_disk_size` int(11) NOT NULL,
  `serie_uncompressed_disk_size` int(11) NOT NULL,
  `manufacturer` tinytext,
  `model_name` tinytext,
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `orthanc_studies`
--

CREATE TABLE `orthanc_studies` (
  `id_visit` int(11) NOT NULL,
  `uploader` varchar(32) DEFAULT NULL,
  `upload_date` datetime DEFAULT NULL,
  `acquisition_date` tinytext,
  `acquisition_time` tinytext,
  `acquisition_datetime` datetime DEFAULT NULL,
  `study_orthanc_id` varchar(44) NOT NULL,
  `anon_from_orthanc_id` varchar(44) NOT NULL,
  `study_uid` tinytext NOT NULL,
  `study_description` tinytext,
  `patient_orthanc_id` varchar(44) NOT NULL,
  `patient_name` tinytext,
  `patient_id` tinytext NOT NULL,
  `number_of_series` int(11) NOT NULL,
  `number_of_instances` int(11) NOT NULL,
  `disk_size` int(11) NOT NULL,
  `uncompressed_disk_size` int(11) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `code` bigint(13) NOT NULL,
  `first_name` varchar(1) DEFAULT NULL,
  `last_name` varchar(1) DEFAULT NULL,
  `gender` varchar(1) DEFAULT NULL,
  `birth_day` int(11) DEFAULT NULL,
  `birth_month` int(11) DEFAULT NULL,
  `birth_year` int(11) DEFAULT NULL,
  `registration_date` date NOT NULL,
  `investigator_name` tinytext,
  `center` int(11) DEFAULT NULL,
  `study` varchar(32) DEFAULT NULL,
  `withdraw_reason` tinytext,
  `withdraw` tinyint(1) NOT NULL DEFAULT '0',
  `withdraw_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `preferences`
--

CREATE TABLE `preferences` (
  `patient_code_length` int(2) NOT NULL,
  `name` varchar(32) NOT NULL,
  `admin_email` tinytext NOT NULL,
  `email_reply_to` tinytext NOT NULL,
  `corporation` varchar(32) NOT NULL,
  `address` tinytext NOT NULL,
  `parse_date_import` varchar(10) NOT NULL DEFAULT 'm.d.Y',
  `parse_country_name` varchar(2) NOT NULL DEFAULT 'US',
  `orthanc_exposed_internal_address` tinytext NOT NULL,
  `orthanc_exposed_internal_port` int(11) NOT NULL,
  `orthanc_exposed_external_address` tinytext NOT NULL,
  `orthanc_exposed_external_port` int(11) NOT NULL,
  `orthanc_exposed_internal_login` tinytext NOT NULL,
  `orthanc_exposed_internal_password` tinytext NOT NULL,
  `orthanc_exposed_external_login` tinytext NOT NULL,
  `orthanc_exposed_external_password` tinytext NOT NULL,
  `orthanc_pacs_address` tinytext NOT NULL,
  `orthanc_pacs_port` int(11) NOT NULL,
  `orthanc_pacs_login` tinytext NOT NULL,
  `orthanc_pacs_password` tinytext NOT NULL,
  `use_smtp` tinyint(1) NOT NULL,
  `smtp_host` tinytext NOT NULL,
  `smtp_port` int(11) NOT NULL,
  `smtp_user` tinytext NOT NULL,
  `smtp_password` tinytext NOT NULL,
  `smtp_secure` varchar(32) NOT NULL DEFAULT 'ssl'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `preferences`
--

INSERT INTO `preferences` (`patient_code_length`, `name`, `admin_email`, `email_reply_to`, `corporation`, `address`, `parse_date_import`, `parse_country_name`, `orthanc_exposed_internal_address`, `orthanc_exposed_internal_port`, `orthanc_exposed_external_address`, `orthanc_exposed_external_port`, `orthanc_exposed_internal_login`, `orthanc_exposed_internal_password`, `orthanc_exposed_external_login`, `orthanc_exposed_external_password`, `orthanc_pacs_address`, `orthanc_pacs_port`, `orthanc_pacs_login`, `orthanc_pacs_password`, `use_smtp`, `smtp_host`, `smtp_port`, `smtp_user`, `smtp_password`, `smtp_secure`) VALUES
(14, 'GaelO', 'administrator@gaelo.fr', '', 'GaelO', 'GaelO.fr', 'm.d.Y', 'US', 'http://orthancexposed', 8042, '', 0, 'internal', 'GaelO', 'external', 'GaelO', 'http://orthancpacs', 8042, 'GaelO', 'GaelO', 0, '', 0, '', '', 'ssl');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id_review` int(11) NOT NULL,
  `id_visit` int(11) NOT NULL,
  `username` varchar(32) NOT NULL,
  `review_date` datetime NOT NULL,
  `validated` tinyint(1) NOT NULL DEFAULT '0',
  `is_local` tinyint(1) NOT NULL DEFAULT '1',
  `is_adjudication` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `name` varchar(32) NOT NULL,
  `username` varchar(32) NOT NULL,
  `study` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `select_roles`
--

CREATE TABLE `select_roles` (
  `role` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `select_roles`
--

INSERT INTO `select_roles` (`role`) VALUES
('Controller'),
('Investigator'),
('Monitor'),
('Reviewer'),
('Supervisor');

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `quality_state` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`quality_state`) VALUES
('Accepted'),
('Corrective Action Asked'),
('Not Done'),
('Refused'),
('Wait Definitive Conclusion');

-- --------------------------------------------------------

--
-- Table structure for table `studies`
--

CREATE TABLE `studies` (
  `name` varchar(32) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tracker`
--

CREATE TABLE `tracker` (
  `date` datetime(6) NOT NULL,
  `username` varchar(32) NOT NULL,
  `role` varchar(32) NOT NULL,
  `study` varchar(32) DEFAULT NULL,
  `id_visit` int(11) DEFAULT NULL,
  `action_type` varchar(32) NOT NULL,
  `action_details` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `username` varchar(32) NOT NULL,
  `last_name` varchar(32) NOT NULL,
  `first_name` varchar(32) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `previous_password_1` varchar(255) DEFAULT NULL,
  `previous_password_2` varchar(255) DEFAULT NULL,
  `temp_password` varchar(255) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `creation_date_password` date NOT NULL,
  `connexion_date` datetime DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `status` set('Blocked','Deactivated','Unconfirmed','Activated') NOT NULL DEFAULT 'Unconfirmed',
  `center` int(11) NOT NULL,
  `job` varchar(32) NOT NULL,
  `number_attempts` int(11) NOT NULL DEFAULT '0',
  `is_administrator` tinyint(1) NOT NULL DEFAULT '0',
  `orthanc_address` tinytext,
  `orthanc_login` tinytext,
  `orthanc_password` tinytext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`username`, `last_name`, `first_name`, `email`, `password`, `previous_password_1`, `previous_password_2`, `temp_password`, `phone`, `creation_date_password`, `connexion_date`, `creation_date`, `status`, `center`, `job`, `number_attempts`, `is_administrator`, `orthanc_address`, `orthanc_login`, `orthanc_password`) VALUES
('administrator', 'administrator', 'administrator', 'administrator@administrator.fr', '$2y$10$vUWuSwuL7dFkR4/AvjpS/eDkSuPlTGKwCu8BerSa8Acv.G3T7vnBa', NULL, NULL, NULL, '', '2019-01-01', '2019-01-01 00:00:00', '2019-01-01 00:00:00', 'Activated', 0, 'Monitor', 0, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id_visit` int(11) NOT NULL,
  `creator_name` varchar(32) DEFAULT NULL,
  `creation_date` datetime DEFAULT NULL,
  `patient_code` bigint(13) NOT NULL,
  `acquisition_date` date DEFAULT NULL,
  `study` varchar(32) NOT NULL,
  `visit_type` varchar(32) NOT NULL,
  `status_done` enum('Not Done','Done') NOT NULL DEFAULT 'Not Done',
  `reason_for_not_done` tinytext,
  `upload_status` enum('Not Done','Processing','Done') NOT NULL DEFAULT 'Not Done',
  `state_investigator_form` enum('Not Done','Draft','Done') NOT NULL DEFAULT 'Not Done',
  `state_quality_control` enum('Not Done','Wait Definitive Conclusion','Corrective Action Asked','Refused','Accepted') NOT NULL DEFAULT 'Not Done',
  `controller_username` varchar(32) DEFAULT NULL,
  `control_date` datetime DEFAULT NULL,
  `image_quality_control` tinyint(1) NOT NULL DEFAULT '0',
  `form_quality_control` tinyint(1) NOT NULL DEFAULT '0',
  `image_quality_comment` tinytext,
  `form_quality_comment` tinytext,
  `corrective_action_username` varchar(32) DEFAULT NULL,
  `corrective_action_date` datetime DEFAULT NULL,
  `corrective_action_new_upload` tinyint(1) NOT NULL DEFAULT '0',
  `corrective_action_investigator_form` tinyint(1) DEFAULT NULL,
  `corrective_action_other` tinytext,
  `corrective_action_decision` tinyint(1) DEFAULT NULL,
  `review_available` tinyint(1) NOT NULL DEFAULT '0',
  `review_status` set('Not Done','Ongoing','Wait Adjudication','Done') NOT NULL DEFAULT 'Not Done',
  `review_conclusion_value` tinytext,
  `review_conclusion_date` datetime DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `visit_type`
--

CREATE TABLE `visit_type` (
  `study` varchar(32) NOT NULL,
  `name` varchar(32) NOT NULL,
  `table_review_specific` varchar(70) NOT NULL,
  `visit_order` int(11) NOT NULL,
  `anon_profile` set('Default','Full') NOT NULL,
  `limit_low_days` int(11) DEFAULT NULL,
  `limit_up_days` int(11) NOT NULL,
  `local_form_needed` tinyint(1) NOT NULL,
  `optional` tinyint(1) NOT NULL,
  `qc_needed` tinyint(1) NOT NULL,
  `review_needed` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `affiliated_centers`
--
ALTER TABLE `affiliated_centers`
  ADD PRIMARY KEY (`username`,`center`),
  ADD KEY `fk_center` (`center`),
  ADD KEY `id_Utilisateur` (`username`);

--
-- Indexes for table `centers`
--
ALTER TABLE `centers`
  ADD PRIMARY KEY (`code`),
  ADD UNIQUE KEY `name_center` (`name`),
  ADD KEY `country_code` (`country_code`);

--
-- Indexes for table `country`
--
ALTER TABLE `country`
  ADD PRIMARY KEY (`country_code`),
  ADD UNIQUE KEY `country_us` (`country_us`),
  ADD UNIQUE KEY `country_fr_2` (`country_fr`),
  ADD KEY `country_fr` (`country_fr`);

--
-- Indexes for table `documentation`
--
ALTER TABLE `documentation`
  ADD PRIMARY KEY (`id_documentation`),
  ADD UNIQUE KEY `name` (`name`,`study`),
  ADD KEY `document_study` (`study`);

--
-- Indexes for table `job`
--
ALTER TABLE `job`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `orthanc_series`
--
ALTER TABLE `orthanc_series`
  ADD PRIMARY KEY (`series_orthanc_id`),
  ADD KEY `Study_Orthanc_Id` (`study_orthanc_id`);

--
-- Indexes for table `orthanc_studies`
--
ALTER TABLE `orthanc_studies`
  ADD PRIMARY KEY (`study_orthanc_id`) USING BTREE,
  ADD KEY `id_visit` (`id_visit`) USING BTREE,
  ADD KEY `uploader` (`uploader`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`code`),
  ADD KEY `nom_etude` (`study`),
  ADD KEY `numero_centre` (`center`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id_review`),
  ADD KEY `id_visite` (`id_visit`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`name`,`username`,`study`),
  ADD KEY `nom_etude` (`study`),
  ADD KEY `nom_role` (`name`),
  ADD KEY `role_user` (`username`);

--
-- Indexes for table `select_roles`
--
ALTER TABLE `select_roles`
  ADD PRIMARY KEY (`role`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`quality_state`);

--
-- Indexes for table `studies`
--
ALTER TABLE `studies`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `tracker`
--
ALTER TABLE `tracker`
  ADD PRIMARY KEY (`date`,`username`,`role`),
  ADD KEY `username` (`username`),
  ADD KEY `role` (`role`),
  ADD KEY `study` (`study`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `numero_centre` (`center`),
  ADD KEY `nom_job` (`job`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id_visit`),
  ADD KEY `type_visite` (`visit_type`),
  ADD KEY `state_control_reviewer` (`review_status`),
  ADD KEY `numero_patient` (`patient_code`),
  ADD KEY `status_done_2` (`status_done`),
  ADD KEY `controller_username` (`controller_username`),
  ADD KEY `corrective_action_username` (`corrective_action_username`),
  ADD KEY `study` (`study`),
  ADD KEY `fk_type_Visite` (`study`,`visit_type`),
  ADD KEY `creator_name` (`creator_name`);

--
-- Indexes for table `visit_type`
--
ALTER TABLE `visit_type`
  ADD PRIMARY KEY (`study`,`name`),
  ADD UNIQUE KEY `study_2` (`study`,`visit_order`),
  ADD KEY `type_visite_ibfk_2` (`name`),
  ADD KEY `study` (`study`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documentation`
--
ALTER TABLE `documentation`
  MODIFY `id_documentation` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id_review` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id_visit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `affiliated_centers`
--
ALTER TABLE `affiliated_centers`
  ADD CONSTRAINT `fk_username` FOREIGN KEY (`username`) REFERENCES `users` (`username`),
  ADD CONSTRAINT `numCentre` FOREIGN KEY (`center`) REFERENCES `centers` (`code`);

--
-- Constraints for table `centers`
--
ALTER TABLE `centers`
  ADD CONSTRAINT `country_center` FOREIGN KEY (`country_code`) REFERENCES `country` (`country_code`);

--
-- Constraints for table `documentation`
--
ALTER TABLE `documentation`
  ADD CONSTRAINT `fk_document_etudet` FOREIGN KEY (`study`) REFERENCES `studies` (`name`);

--
-- Constraints for table `orthanc_series`
--
ALTER TABLE `orthanc_series`
  ADD CONSTRAINT `fk_study_orthanc_id` FOREIGN KEY (`study_orthanc_id`) REFERENCES `orthanc_studies` (`study_orthanc_id`);

--
-- Constraints for table `orthanc_studies`
--
ALTER TABLE `orthanc_studies`
  ADD CONSTRAINT `idvisit_from_visits` FOREIGN KEY (`id_visit`) REFERENCES `visits` (`id_visit`),
  ADD CONSTRAINT `uploader_from_user` FOREIGN KEY (`uploader`) REFERENCES `users` (`username`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_center_nb` FOREIGN KEY (`center`) REFERENCES `centers` (`code`),
  ADD CONSTRAINT `fk_study` FOREIGN KEY (`study`) REFERENCES `studies` (`name`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_username_review` FOREIGN KEY (`username`) REFERENCES `users` (`username`),
  ADD CONSTRAINT `id_visit_from_visits_review` FOREIGN KEY (`id_visit`) REFERENCES `visits` (`id_visit`);

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `role_name` FOREIGN KEY (`name`) REFERENCES `select_roles` (`role`),
  ADD CONSTRAINT `role_study` FOREIGN KEY (`study`) REFERENCES `studies` (`name`),
  ADD CONSTRAINT `role_user` FOREIGN KEY (`username`) REFERENCES `users` (`username`);

--
-- Constraints for table `tracker`
--
ALTER TABLE `tracker`
  ADD CONSTRAINT `fk_study_tracker` FOREIGN KEY (`study`) REFERENCES `studies` (`name`),
  ADD CONSTRAINT `fk_username_tracker` FOREIGN KEY (`username`) REFERENCES `users` (`username`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `job` FOREIGN KEY (`job`) REFERENCES `job` (`name`),
  ADD CONSTRAINT `num_centre` FOREIGN KEY (`center`) REFERENCES `centers` (`code`);

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `creatorUsername` FOREIGN KEY (`creator_name`) REFERENCES `users` (`username`),
  ADD CONSTRAINT `patientNumber` FOREIGN KEY (`patient_code`) REFERENCES `patients` (`code`),
  ADD CONSTRAINT `usernameController` FOREIGN KEY (`controller_username`) REFERENCES `users` (`username`),
  ADD CONSTRAINT `usernameCorrective` FOREIGN KEY (`corrective_action_username`) REFERENCES `users` (`username`),
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`study`,`visit_type`) REFERENCES `visit_type` (`study`, `name`);

--
-- Constraints for table `visit_type`
--
ALTER TABLE `visit_type`
  ADD CONSTRAINT `visit_type_ibfk_1` FOREIGN KEY (`study`) REFERENCES `studies` (`name`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
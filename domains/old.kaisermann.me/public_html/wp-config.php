<?php
/** 
 * As configurações básicas do WordPress.
 *
 * Esse arquivo contém as seguintes configurações: configurações de MySQL, Prefixo de Tabelas,
 * Chaves secretas, Idioma do WordPress, e ABSPATH. Você pode encontrar mais informações
 * visitando {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. Você pode obter as configurações de MySQL de seu servidor de hospedagem.
 *
 * Esse arquivo é usado pelo script ed criação wp-config.php durante a
 * instalação. Você não precisa usar o site, você pode apenas salvar esse arquivo
 * como "wp-config.php" e preencher os valores.
 *
 * @package WordPress
 */

// ** Configurações do MySQL - Você pode pegar essas informações com o serviço de hospedagem ** //
/** O nome do banco de dados do WordPress */
define('DB_NAME', 'old');


/** Usuário do banco de dados MySQL */
define('DB_USER', 'old.login.old');


/** Senha do banco de dados MySQL */
define('DB_PASSWORD', 'm745zu_ezS^vd=cM');


/** nome do host do MySQL */
define('DB_HOST', 'localhost');


/** Conjunto de caracteres do banco de dados a ser usado na criação das tabelas. */
define('DB_CHARSET', 'utf8');

/** O tipo de collate do banco de dados. Não altere isso se tiver dúvidas. */
define('DB_COLLATE', '');

/**#@+
 * Chaves únicas de autenticação e salts.
 *
 * Altere cada chave para um frase única!
 * Você pode gerá-las usando o {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * Você pode alterá-las a qualquer momento para desvalidar quaisquer cookies existentes. Isto irá forçar todos os usuários a fazerem login novamente.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '|pw,T@(aRI3qO_[_fV6.VxFc/*[K|FF1Qj+3LaJd:i_>|TRbKI|~uGh?`88uEV_R');

define('SECURE_AUTH_KEY',  'ErMRa|[Nj!`<nQw-q)+>+o|JhSMTIR-%fXrr,KqCiMp<,}#X:2{b;Ke2i9h<4]FF');

define('LOGGED_IN_KEY',    '+)~G4<4|_,rh@:Hs|T|F{DU.PeG#ub W<G++aIuLdsiE/b.#Hfy%/b-Rjv&`M!+#');

define('NONCE_KEY',        '${y)w+k)AY]%qs(u_rAV72{4dWfg}FpO(;Q7?!tX7D`6)VXo)@ Z{Q;iIw3_;Tw`');

define('AUTH_SALT',        'a-g$IqFEEV`d{<|?r-5A/VvO:A@|EO])CZV(X4&Sv^$1PEkDM`krbxN/m(,>AmtP');

define('SECURE_AUTH_SALT', 'T0}o1gs~KX@4VBOFoQKuk75xyBcC:]5u-t`+v@{&0E~}^E-(-k<ZEx+O*ZoUv<~6');

define('LOGGED_IN_SALT',   'UdM--DHAH?5naWsb$ap|-fzySih4~t+6t0NsV6hQ7|5T0T-&t;6m1k^1h:p[|qtn');

define('NONCE_SALT',       'BX{Q.k5HL_(R9GY}f?Ubs|?xf}]y6^v1+.iG,,fcxMNb{PF.ocDl:jo8UC6,eeB=');


/**#@-*/

/**
 * Prefixo da tabela do banco de dados do WordPress.
 *
 * Você pode ter várias instalações em um único banco de dados se você der para cada um um único
 * prefixo. Somente números, letras e sublinhados!
 */
$table_prefix  = 'kaiser_';


/**
 * O idioma localizado do WordPress é o inglês por padrão.
 *
 * Altere esta definição para localizar o WordPress. Um arquivo MO correspondente ao
 * idioma escolhido deve ser instalado em wp-content/languages. Por exemplo, instale
 * pt_BR.mo em wp-content/languages e altere WPLANG para 'pt_BR' para habilitar o suporte
 * ao português do Brasil.
 */
define('WPLANG', 'pt_BR');

/**
 * Para desenvolvedores: Modo debugging WordPress.
 *
 * altere isto para true para ativar a exibição de avisos durante o desenvolvimento.
 * é altamente recomendável que os desenvolvedores de plugins e temas usem o WP_DEBUG
 * em seus ambientes de desenvolvimento.
 */
define('WP_DEBUG', false);

/* Isto é tudo, pode parar de editar! :) */

/** Caminho absoluto para o diretório WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Configura as variáveis do WordPress e arquivos inclusos. */
require_once(ABSPATH . 'wp-settings.php');

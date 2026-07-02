/* eslint no-console: 0 */
// console.log('start sass-builder.js ...');

const path = require( 'path' );
const fs = require( 'fs' );

// dart-sass（node-sassの後継。レガシーrender APIはnode-sass互換のimporterをそのまま使える）
const sass = require( 'sass' );
const globImporter = require( 'node-sass-glob-importer' );

// postcss
const postcss = require( 'postcss' );
const autoprefixer = require( 'autoprefixer' );
const cssnano = require( 'cssnano' );
const mqpacker = require( 'css-mqpacker' );

const isWatch = process.argv.includes( '--watch' );

// consoleの色付け
const red = '\u001b[31m';
const green = '\u001b[32m';

const writeCSS = ( filePath, css ) => {
	const dir = path.dirname( filePath );

	// ディレクトリがなければ作成
	if ( ! fs.existsSync( dir ) ) {
		fs.mkdirSync( dir, { recursive: true } );
	}

	// css書き出し
	fs.writeFileSync( filePath, css );
};

// パス
const src = 'src/scss';
const dist = 'dist/css';
const files = [
	'icon',
	'main',
	'editor',
	'admin/menu',
	'admin/customizer',
	'admin/nav-menus',
	'admin/edit-table',
	'module/luminous',
	'module/-overlay-header',
];

const buildOne = ( fileName ) => {
	// renderSyncだとimporter使えない
	sass.render(
		{
			file: path.resolve( __dirname, src, `${ fileName }.scss` ),
			outputStyle: 'compressed',
			importer: globImporter(),
			logger: sass.Logger.silent,
		},
		function ( err, sassResult ) {
			if ( err ) {
				console.error( red + err );
			} else {
				const css = sassResult.css.toString();
				const filePath = path.resolve( __dirname, dist, `${ fileName }.css` );

				// postcss実行
				postcss( [ autoprefixer, mqpacker, cssnano ] )
					.process( css, { from: undefined } )
					.then( ( postcssResult ) => {
						console.log( green + 'Wrote CSS to ' + filePath );
						writeCSS( filePath, postcssResult.css );

						// if (postcssResult.map) {fs.writeFile('dest/app.css.map', postcssResult.map.toString(), () => true);}
					} );
			}
		}
	);
};

const buildAll = () => {
	files.forEach( buildOne );
};

buildAll();

if ( isWatch ) {
	const chokidar = require( 'chokidar' );

	console.log( 'Watching for changes in ' + src + ' ...' );

	chokidar
		.watch( path.resolve( __dirname, src, '**/*.scss' ) )
		.on( 'change', ( changedPath ) => {
			console.log( 'Changed: ' + changedPath );
			buildAll();
		} );
}

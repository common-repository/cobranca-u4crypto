<?php

/** function submenu status */
function u4crypto_page_status() {
    $file = U4CRYPTO_PLUGIN_DIR.'tmp/errors.json';
    if(file_exists($file)){
        $log = 'Arquivo de erro existe';
    }else{
        try{
            $archive = fopen(U4CRYPTO_PLUGIN_DIR.'tmp/errors.json', 'a');
            fwrite($archive, '');
            fclose($archive);
            $log = 'Atualize a página.';
        }catch(Exception $e){
            $log = 'Arquivo de erro não existe - '.$e->getMessage();
        }
    }

    _e( '<div class="wrap"><h2>Status</h2></div>
    <table class="wc_status_table striped widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="WordPress Environment"><h2>Status do Plugin e servidor</h2></th>
		</tr>
	</thead>
	<tbody>
		');
        _e('
                <tr>
                    <td>Limite de memória do WordPress:</td>');
                    $mrylmt = ini_get('memory_limit');
                    if($mrylmt < 512){
                        _e( '
                        <td>
                            <div style="color:red"><span class="dashicons dashicons-dismiss"></span> '.$mrylmt.'</div>
                        </td>
                        <td>
                            Define a quantidade máxima de memória que um script pode alocar. A sua está abaixo do Requisitado no momento.
                        </td>');
                    }else if($mrylmt >= 512 && $mrylmt < 1000){
                        _e( '
                        <td>
                            <div style="color:orange"><span class="dashicons dashicons-warning"></span> '.$mrylmt.'</div>
                        </td>
                        <td>
                            Define a quantidade máxima de memória que um script pode alocar. A sua está no mínimo Requisitado no momento.
                        </td>');
                    }else{
                        _e( '
                        <td>
                            <div style="color:green"><span class="dashicons dashicons-yes-alt"></span> '.$mrylmt.'</div>
                        </td>
                        <td>
                            Define a quantidade máxima de memória que um script pode alocar. A sua está acima do Requisitado no momento.
                        </td>');
                    }
                    _e('
                </tr>
                <tr>
                    <td>Versão do PHP:</td>');
                    $phpv = phpversion();
                    if($phpv < '7.3.00' ){
                        _e( '
                        <td>
                            <div style="color:red"><span class="dashicons dashicons-dismiss"></span> '.$phpv.'</div>
                        </td>
                        <td>A versão do PHP de seu Site está abaixo do Requisitado.</td>');
                    }else if($phpv > '7.3.00' && $phpv < '8.0.00'){
                        _e( '
                        <td>
                            <div style="color:orange"><span class="dashicons dashicons-warning"></span> '.$phpv.'</div>
                        </td>
                        <td>A versão do PHP de seu Site está no mínimo do Requisitado.</td>');
                    }else{
                        _e( '
                        <td>
                            <div style="color:green"><span class="dashicons dashicons-yes-alt"></span> '.$phpv.'</div>
                        </td>
                        <td>A versão do PHP de seu Site está acima do Requisitado.</td>');
                    }
                    _e('
                </tr>
                <tr>
                    <td>Formatação de Números:</td>');
                    if (class_exists('NumberFormatter')) {
                        _e( '
                            <td>
                                <div style="color:green"><span class="dashicons dashicons-yes-alt"></span> Presente</div>
                            </td>
                            <td>A classe NumberFormatter está presente em seu PHP.</td>');
                        } else {
                        _e( '
                        <td>
                            <div style="color:red"><span class="dashicons dashicons-dismiss"></span> Não Presente</div>
                        </td>
                        <td>
                            A classe NumberFormatter não está presente em seu PHP. Ela é extremamente necessária para o funcionamento do plugin.
                        </td>');
                        }
                    _e('
                </tr>
                <tr>
                    <td>Limite de tempo do PHP:</td>');
                    $exctime = ini_get('max_execution_time');
                    if($exctime == 30 || $exctime == 120){
                        _e( '
                        <td>
                            <div style="color:red"><span class="dashicons dashicons-dismiss"></span> '.$exctime.'</div>
                        </td>
                        <td>Isso configura o tempo máximo, em segundos, que um script é permitido executar antes de ser terminado. O seu Tempo Máximo de Execução está fora do Requisitado.</td>');
                    }else if($exctime == 60){
                        _e( '
                        <td>
                            <div style="color:green"><span class="dashicons dashicons-yes-alt"></span> '.$exctime.'</div>
                        </td>
                        <td>Isso configura o tempo máximo, em segundos, que um script é permitido executar antes de ser terminado. O seu Tempo Máximo de Execução está de acordo com o Requisitado.</td>');
                    }
                    _e('
                </tr>
                <tr>
                    <td>Máximo de entrada de variáveis (max input vars) do PHP:</td>');
                    $inptvars = ini_get('max_input_vars');
                    if($inptvars >= 3000 && $inptvars < 5000){
                        _e( '
                        <td>
                            <div style="color:orange"><span class="dashicons dashicons-warning"></span> '.$inptvars.'</div>
                        </td>
                        <td>
                            Configura quantas variáveis de entrada serão aceitas. Você está no mínimo requisitado.
                        </td>');
                    }else if($inptvars >= 5000 && $inptvars < 7000){
                        _e( '
                        <td>
                            <div style="color:green"><span class="dashicons dashicons-yes-alt"></span> '.$inptvars.'</div>
                        </td>
                        <td>
                            Configura quantas variáveis de entrada serão aceitas. Você está acima do requisitado.
                        </td>');
                    }else{
                        _e( '
                        <td>
                            <div style="color:red"><span class="dashicons dashicons-dismiss"></span> '.$inptvars.'</div>
                        </td>
                        <td>
                            Configura quantas variáveis de entrada serão aceitas. Você está abaixo do requisitado.
                        </td>');
                    }
                    _e( '
                </tr>
                ');
                _e('
                <tr>
                    <td>Versão do cURL:</td>
                    <td>');  $v = curl_version();
                            _e( $v["version"]);
                    _e( '</td>
                    <td>
                        O cURL é uma ferramenta para criar requisições em diversos protocolos e obter conteúdo remoto.
                    </td>
                </tr>
                <tr>
                    <td>Memoria usada:</td>
                    <td>');
                    $size = memory_get_usage(true);

                    $unit=array('b','kb','mb','gb','tb','pb');
                    _e( @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i]);
                    _e('</td>
                    <td>Quantidade de Memoria Usada.</td>
                </tr>');
                _e('
                <tr>
                    <td>Arquivo de Log:</td>
                    <td>'.$log.'</td>
                    <td>Arquivo responsável por gravar os logs de erro da integração</td>
                </tr>');
                _e('
        </tbody>
    </table>
    ');
}
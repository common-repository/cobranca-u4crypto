<?php

function u4crypto_page_logs() {
    $dir = U4CRYPTO_PLUGIN_DIR.'tmp/errors.json'; //plugin_dir_path(__DIR__).'tmp/errors.json';

    if(!file_exists($dir)){
        /** cria o arquivo errors.json */
        $file_handle = fopen($dir, "w");
        fclose($file_handle);
    }
    $file_handle = fopen($dir, "r");

    _e( '
    <div class="wrap"><h2>Log</h2></div>
    <div class="wrap">
        <table class="wp-list-table widefat fixed striped table-view-list logs">
        <thead>
            <tr>
                <th scope="col" id="title" class="manage-column column-title"><span>Data</span></th>
                <th scope="col" id="title" class="manage-column column-title"><span>Código</span></th>
                <th scope="col" id="title" class="manage-column column-title"><span>Item</span></th>
                <th scope="col" id="title" class="manage-column column-title"><span>Informações</span></th>
                <th scope="col" id="title" class="manage-column column-title"><span>Resposta</span></th>
            </tr>
        </thead>
        <tbody id="the-list">
    ');

    if(file_exists($dir)){
        while (!feof($file_handle)) {

            $line = fgets($file_handle);
            $data = explode(" - ", $line);
            _e( '
                    <tr>
                        <td>');
                        if(!empty($data[0])){
                            _e( $data[0]);
                        };
                        _e('</td>
                        <td>');
                        if(!empty($data[1])){
                            _e( $data[1]);
                        };
                        _e('</td>
                        <td>');
                        if(!empty($data[2])){
                            _e( $data[2]);
                        };
                        _e('</td>
                        <td>');
                        if(!empty($data[3])){
                            _e( $data[3]);
                        };
                        _e('</td>
                        <td>');
                        if(!empty($data[4])){
                            _e( $data[4]);
                        };
                        _e('</td>
                    </tr>');
        }
    }else{
        _e( '
                    <tr class="no-items">
                        <td class="colspanchange">Nenhum Log encontrado.</td>
                    </tr>');
    }

     _e( '
            </tbody>
            </table>
        </div>');

    if(file_exists($dir)){
        fclose($file_handle);
    }
}
# Requerimentos

Este server storage para uploads requer um ambiente PHP 7+ pré configurado com htmod em apache com shellexec habilitado e diretório com permissão de escrita (de preferência 777).

Para instalar um servidor semelhante pré-configurado com docker, veja o link abaixo.

https://gitlab.com/mayrocolnago/php-server


# Como usar

Basta incluir a tag script apontando para o arquivo **upload.js**

```html
<script src="//storage.localhost/upload.js"></script>
```

Depois é só habilitar a funcionalidade em algum campo do tipo **file** usando *javascript*

```html
<form><input type="file" id="file"></form>

<script>
    var onstart = function(data){ console.log(data); };
    var ondone = function(data){ console.log(data); };
  
    bindupload('#file',{ 'f':'name_sufix', 'p':'path/' }, onstart, ondone);
</script>
```


# Configurações

O arquivo **tokens.php** permite inserção de tokens permanentes. Caso contrário, será sempre necessário fazer o upload chamando o **upload.js** para gerar um token temporário.

No arquivo **upload.php** nas linhas 3, 5 e 6 é possível fazer a configuração do limite de upload e o nível de verificação de segurança dos arquivos (que vai de 1-6).


# Funcionalidades

- Este script é preparado para fazer verificação do tipo de arquivo que está sendo feito upload

- Segurança do conteúdo do arquivo para bloqueio de scripts maliciosos

- Token seguro para upload e auto atribuição de nome e diretório

- Envio imediato do arquivo sem a necessidade de recarregar a página

- Possibilidade de envio de multiplos arquivos e fila de envio

- Verificação de envio bem sucedido e re-tentativa em caso de falha

- Notificação imediata de intenção de upload e retorno após conclusão

- Conversão de imagens no lado do cliente e no lado do servidor

- Parâmetros para configuração de qualidade no envio (ver [upload.php](upload.php))

- Possibilidade de obtenção de arquivo de outra URL

- Possibilidade de envio de arquivo já codificado em base64

- Auto compilação e preenchimento das variáveis de endereço de server e token no arquivo .js
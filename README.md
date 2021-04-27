Versão deste README em Português (Brasil), [clique aqui](README_pt-BR.md)

# Requeriments

This storage server for uploads requires a PHP 7+ environment configured with htmod on an apache with shellexec enabled and a directory with full writen permission (prefered 777).

To install a similar pre-configurated server with docker, follow the link below.

https://gitlab.com/mayrocolnago/php-server


# How to use

Just include a script tag point out to the **upload.js** file

```html
<script src="//storage.localhost/upload.js"></script>
```

After that you just need to enable a **file** field using *javascript*

```html
<form><input type="file" id="file"></form>

<script>
    var onstart = function(data){ console.log(data); };
    var ondone = function(data){ console.log(data); };
  
    bindupload('#file',{ 'f':'name_sufix', 'p':'path/' }, onstart, ondone);
</script>
```


# Configurations

The **tokens.php** allows you to set permanent tokens to upload. Otherwise, you'll always have to use **upload.js** to generate a temporary token for uploads.

On the **upload.php** file at lines 3, 5 and 6 you can configure upload file size limits and security verification levels (which goes from 1 to 6).


# Functionalities

- This script is prepared to verify which kind of file is being uploaded

- It can verifies the security of the file content to protect the server from malicious files

- It does have a temporary security token and a lots of validations to file names and directory

- Imediatly send the uploaded file without page refresh needed

- Possibility of sending multiples files and queue them

- Verifies wheter an upload is successful and retry any failed sending

- Instantly notifies the upload intention and returns the upload information as soon as it finishes

- Converts the sending images on the clients and serverside

- Configurable parameters to set file quality and size (see [upload.php](upload.php))

- Possibility of uploading files from another URL

- Possibility of uploading coded files with base64

- Automatic compilation and fullfilment of server address and token on the .js file variables
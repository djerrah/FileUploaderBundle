README FILE
===========

1) Installation
---------------

### a) Add the bundle to composer.json at the project root ###

    "require": {
        ...
        "djerrah/file-uploader-bundle" : "dev"
    },
    "repositories": [{
        {
            "type" : "vcs",
            "url" : "https://github.com/djerrah/FileUploaderBundle.git"
        }
    }],

### b) Edit app/AppKernel.php file ###

        $bundles = array(
            /* ... */
            new Djerrah\FileUploaderBundle\DjerrahFileUploaderBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
        );
    

### Routing ###

djerrah_uploader:
    resource: "@DjerrahFileUploaderBundle/Resources/config/routing/all.xml"
    prefix: /admin
    
### Update Schema
    
   execute schema:update:force
    
    
### add stof doctrine extension config to config.yml
    
stof_doctrine_extensions:

    orm:
    
        default:
        
            timestampable: true
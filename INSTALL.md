# WASE installation instructions

## Requirements

### OS
WASE will run on just about any Linux system, as well as macOS.  It has been tested
on RedHat and Ubuntu Linux, and on a Mac running Mojave.  It has also run in an Azure and AWS container
built on top of alpine, but this requires the addition of numerous packages, and it probably
makes sense to build the Docker image, if you want to run it as a container, on top of Ubuntu.

### Web Server
WASE is a web application and requires the Nginx web server and the PHP-FPM FastCGI process manager.  
It uses a specific nginx configuration to support multiple institutions/organizations from a single code base. These
instructions do not cover Nginx/PHP-FPM installation, but they do cover Nginx/PHP-FPM configuration.

### Database
WASE requires access to a MySQL database (any version starting with 5 will work).  The database
can run on the same server as WASE, or on a different server (e.g., in Azure).  These instructions
do not cover MySQL installation, but they do cover MySQL configuration.

### Dependency manager
WASE uses composer to manage dependencies, and composer must be installed on the WASE server
to complete the installation.  These instructions do not cover composer installation (see
[https://packagist.org/](https://packagist.org/)).

Once you have a Linux system with Nginx, PHP-FPM, composer, and access to a MySQL sytem,
you are ready to install WASE.

## Installation

### Step 1: Select, or create, "wase" Linux user.

The first step is to decide under what username WASE will run.  I recommend creating a "wase" Linux user specifically for this purpose.
This user will own the WASE code directories.  It is important to assign this user to the same Unix group as the
Nginx server, so that you can give Nginx read access to the WASE source (html, php, javascript) files
using Unix group permissions.  In what follows, I will refer to this user as "wase" (username), and to
its group as "nginx".

### Step 2: Select, or create, WASE installation directory.

The next step is to login to the WASE server as user "wase" and select, or create, a directory into
which WASE will be deployed.  You may well have local conventions about where this should be.  I
recommend creating a directory with a name of the form "wase.n.n.n", where the three n's correspond to the WASE
release of WASE that you are installing.  For example, "wase.2.4.1".  You can then create a "wase" symbolic link which points to
the "current" WASE release.  Make sure that the "wase.n.n.n" directory is owned by user "wase", and is
in group "nginx", and that it has 740 permissions.


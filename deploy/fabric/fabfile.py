from fabric.api import *

# configure here
# which hosts are in what roles
# you will want to edit this
env.roledefs = {
    'webapps': ['m6-webapp-1.emerson.edu'],
    'files': ['m6-files-1.emerson.edu'],
    'streaming': ['m6-streaming-1.emerson.edu', 'm6-streaming-2.emerson.edu'],
    'monitor': ['m6-monitor.emerson.edu']
}

# log in as root, of course...
env.user = 'root'

# use your ssh config file
env.use_ssh_config = True

# test stuff
def ubuntu_info():
	run('lsb_release -d')

def host_type():
    run('uname -s')

def host_info():
    run('uname -a')

def get_ips():
    run('ifconfig eth0 | grep "inet addr"')

# real stuff
@roles('webapps')
def update_webapp_code():
    with cd('/median-webapp'):
        run('git pull')

@roles('webapps')
def update_webapp_config():
    with cd('/root/m6-deploy-webapp'):
        run('git pull')
        run('cp lighty-php5-fpm.conf /etc/lighttpd/conf-enabled/15-php5-fpm.conf')
        run('cp lighty-simplesaml.conf /etc/lighttpd/conf-enabled/20-simplesaml.conf')
        restart_webapp_services()

@roles('webapps')
def restart_webapp_services():
    run('service lighttpd restart')
    run('service php5-fpm restart')

@roles('streaming')
def restart_streaming():
    run('restart nginx-rtmp')

@roles('files')
def update_fileapi_code():
    with cd('/median-file-api'):
        run('git pull')
    restart_file_api()

@roles('files')
def restart_file_api():
    #run('pm2 restart 0')
    run('forever restart 0')

@roles('monitor')
def update_monitor_code():
    with cd('/median-monitor'):
        run('git pull')
    restart_monitor_service()

@roles('monitor')
def restart_monitor_service():
    run('pm2 restart 0')

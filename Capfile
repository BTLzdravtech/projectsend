require 'capistrano/setup'
require 'capistrano/deploy'

require 'capistrano/scm/git'
install_plugin Capistrano::SCM::Git

require 'capistrano/npm'
require 'capistrano/composer'
require 'capistrano/gulp'
require 'capistrano/hangouts_chat'
require 'capistrano/php_fpm/systemd'

Dir.glob('lib/capistrano/tasks/*.rake').each { |r| import r }

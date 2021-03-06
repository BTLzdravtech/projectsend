lock '~> 3.11.0'

set :application, 'projectsend'

set :repo_url, 'https://github.com/BTLzdravtech/projectsend.git'

set :deploy_to, '/home/deployer/projectsend'

set :local_user, -> { `git config user.name`.chomp }

set :format_options, color: true

append :linked_files, '.env', 'includes/sys.config.php'
append :linked_dirs, 'cache'

set :npm_flags, '--silent --no-progress'
set :gulp_tasks, 'prod'

after 'deploy:updated', 'gulp'

set :webhook_url, 'https://chat.googleapis.com/v1/spaces/AAAASq4T8BQ/messages?key=AIzaSyDdI0hCZtE6vySjMm-WEfRq3CPzqKqqsHI&token=7bYqjNOCKqqGtkaC--sqTaqyPmsWW4CbEnXlm4-i6-k%3D'

header = { title: 'Project SEND',
           subtitle: 'new build just deployed',
           imageUrl: 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/Capistrano_logo.svg/256px-Capistrano_logo.svg.png',
           imageStyle: 'AVATAR' }
sections = [{ widgets: [{ keyValue: { topLabel: 'Deployed by', content: `git config user.name`.chomp.encode('UTF-8', 'UTF-8'), button: { textButton: { text: 'VISIT WEBSITE', onClick: { openLink: { url: 'https://send.medictech.com' }}}}}}]}]
message = { cards: [header: header, sections: sections] }

set :message, message

set :php_fpm_roles, :app
set :php_fpm_service_name, 'php7.3-fpm'
after 'deploy:finished', 'php_fpm:restart'

after 'deploy:finished', 'hangouts_chat:send_message'

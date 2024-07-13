require 'bundler/setup'

require 'rubocop/rake_task'
RuboCop::RakeTask.new(:rubocop) do |task|
  # If you use mediawiki-vagrant, rubocop will by default use its .rubocop.yml.

  # This line makes it explicit that you want .rubocop.yml from the directory
  # where `bundle exec rake` is executed:
  task.options = ['-c', '.rubocop.yml']
end

task :default => [:test]

desc 'Run all build/tests commands (CI entry point)'
task :test => [:rubocop]

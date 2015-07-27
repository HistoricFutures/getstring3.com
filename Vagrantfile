# @file
# Vagrantfile.

Vagrant.configure(2) do |config|
  config.vm.box = "ubuntu/trusty64"
  config.vm.network "forwarded_port", guest: 80, host: 8000

  config.vm.provision :ansible do |ansible|
    ansible.playbook = "playbook.yml"

    # Get Ansible SSH to match "vagrant ssh".
    ansible.raw_ssh_args = [
      '-o Compression=yes',
      '-o DSAAuthentication=yes',
      '-o LogLevel=FATAL',
      '-o StrictHostKeyChecking=no',
      '-o IdentitiesOnly=yes'
    ]
  end
end

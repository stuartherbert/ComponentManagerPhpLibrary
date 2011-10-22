<?php

require_once "phing/Task.php";

/**
 * Attempts to call a target in the loaded Phing build.xml just like
 * PhingCallTarget, but does not error if the target does not exist.
 * 
 * NOTE: This class conforms to Phing's class-naming scheme, which is
 * not PSR-0 compatible.
 */
class PhingCallIfExistsTask extends Task
{
	protected $callee;
	protected $targetname;
	
	public function setTarget($target)
	{
		$this->targetname = $target;
	}
	
	public function init()
	{
		$this->callee = $this->project->createTask('phing');
		$this->callee->setOwningTarget($this->getOwningTarget());
		$this->callee->setTaskName($this->getTaskName());
		$this->callee->setHaltOnFailure(true);
		$this->callee->setLocation($this->getLocation());
		$this->callee->init();
	}
	
	public function main()
	{
		$this->log("Running PhingCallIfExists for target '{$this->targetname}'.", Project::MSG_DEBUG);
		
		if( ! $this->callee)
		{
			$this->init();
		}
		
		if( ! $this->targetname)
		{
			throw new BuildException("Attribute target is required.", $this->getLocation());
		}
		
		$targets = $this->project->getTargets();
		
		if( ! isset($targets[$this->targetname]))
		{
			$this->log("Aborting PhingCallIfExists for target '{$this->targetname}', target does not exist.", Project::MSG_DEBUG);
			
			return;
		}
		
		$this->callee->setPhingfile($this->project->getProperty("phing.file"));
		$this->callee->setTarget($this->targetname);
		$this->callee->setInheritAll(false);
		$this->callee->setInheritRefs(false);
		$this->callee->main();
	}
}
<?php 
namespace Mouf\Services;

use Mouf\Widgets\Package;
use Mouf\Widgets\Section;

/**
 * A service to find all the packages installed.
 * 
 * @author XAH
 */
class SectionBuilder {

    const MAX_WEIGHT = 1000;

	/**
	 * Returns the list of all branch versions for the package in directory $dir
	 */
	public function buildSections($packageExplorer) {
        $packages = $packageExplorer->getPackages();
        $sections = array();
        $currentMaxWeight = 999;
        // Let's fill the menu with the packages.
        foreach ($packages as $owner=>$packageList) {
            foreach ($packageList as $packageName) {
                $package = $packageExplorer->getPackage($owner.'/'.$packageName);
                $latestPackageVersion = $package->getLatest();
                if($latestPackageVersion == null){
                    continue;
                }
                $packageVersion = $package->getPackageVersion($latestPackageVersion);
                $composerJson = $packageVersion->getComposerJson();
                if(isset($composerJson['extra']['mouf']['section'])){
                    if(isset($composerJson['extra']['mouf']['section']['name'])){
                        $sectionName = $composerJson['extra']['mouf']['section']['name'];
                        if(isset($sections[$sectionName])){
                            $sections[$sectionName]->addPackage($package);
                        }else{
                            $section = new Section($sectionName);
                            $sections[$sectionName] = $section;
                            $sections[$sectionName]->addPackage($package);
                        }
                        if(isset($composerJson['extra']['mouf']['section']['weight'])){
                            $sectionWeight = $composerJson['extra']['mouf']['section']['weight'];
                            $sections[$sectionName]->setWeight($sectionWeight);

                        }else{
                            $sections[$sectionName]->setWeight($currentMaxWeight);
                            $currentMaxWeight--;
                        }
                        if(isset($composerJson['extra']['mouf']['section']['description'])){
                            $sectionDescription = $composerJson['extra']['mouf']['section']['description'];
                            $sections[$sectionName]->setDescription($sectionDescription);
                        }

                    }
                }
                else{
                    $sectionName = "Other";
                    if(isset($sections[$sectionName])){
                        $sections[$sectionName]->addPackage($package);
                    }else{
                        $section = new Section($sectionName);
                        $sections[$sectionName] = $section;
                        $section->setWeight(self::MAX_WEIGHT);
                        $sections[$sectionName]->addPackage($package);
                    }
                }
            }
        }

        usort($sections,function(Section $section1, Section $section2){
                return $section1->getWeight() - $section2->getWeight();
            });
        return $sections;
	}

}

?>
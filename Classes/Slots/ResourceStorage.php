<?php
declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\FalProtect\Slots;

use Causal\FalProtect\Domain\Repository\FolderRepository;
use Causal\FalProtect\Traits\UpdateSubfoldersTrait;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This slot is used when running TYPO3 v9.
 *
 * Class ResourceStorage
 * @package Causal\FalProtect\Slots
 */
class ResourceStorage implements SingletonInterface
{

    use UpdateSubfoldersTrait;

    /**
     * @var Folder
     */
    protected $previousFolder;

    /**
     * ResourceStorage constructor.
     */
    public function __construct()
    {
        $this->folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
    }

    /**
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param string $newName
     */
    public function preFolderCopy(Folder $folder, Folder $targetFolder, string $newName): void
    {
        $this->populatePreviousFolderMapping($folder);
    }

    /**
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param $newName
     */
    public function postFolderCopy(Folder $folder, Folder $targetFolder, string $newName): void
    {
        $newFolder = $targetFolder->getSubfolder($newName);
        $this->folderRepository->copyRestrictions($folder, $newFolder);
        $this->copyRestrictionsFromSubfolders($folder, $newFolder);
    }

    /**
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param string $newName
     */
    public function preFolderMove(Folder $folder, Folder $targetFolder, string $newName): void
    {
        $this->populatePreviousFolderMapping($folder);
    }

    /**
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param string $newName
     * @param Folder $originalParentFolder
     */
    public function postFolderMove(Folder $folder, Folder $targetFolder, string $newName, Folder $originalParentFolder): void
    {
        $newFolder = $targetFolder->getSubfolder($newName);
        $this->folderRepository->moveRestrictions($folder, $newFolder);
        $this->moveRestrictionsFromSubfolders($folder, $newFolder);
    }

    /**
     * @param Folder $folder
     * @param string $newName
     */
    public function preFolderRename(Folder $folder, string $newName): void
    {
        $this->previousFolder = $folder;
        $this->populatePreviousFolderMapping($folder);
    }

    /**
     * @param Folder $folder
     * @param string $newName
     */
    public function postFolderRename(Folder $folder, string $newName): void
    {
        if ($folder->getIdentifier() === $this->previousFolder->getIdentifier()) {
            // This is a known bug: https://forge.typo3.org/issues/92790
            $newIdentifier = dirname($folder->getIdentifier()) . '/' . $newName . '/';
            $folder = $folder->getStorage()->getFolder($newIdentifier);
        }
        $this->folderRepository->moveRestrictions($this->previousFolder, $folder);
        $this->moveRestrictionsFromSubfolders($this->previousFolder, $folder);
    }

    /**
     * @param Folder $folder
     */
    public function preFolderDelete(Folder $folder): void
    {
        $this->populatePreviousFolderMapping($folder);
    }

    /**
     * @param Folder $folder
     */
    public function postFolderDelete(Folder $folder): void
    {
        $this->folderRepository->deleteRestrictions($folder);
        $this->deleteRestrictionsFromSubfolders($folder);
    }

}

<?php

class DocumentManager {
    private $userDocumentDir;

    public function __construct($userId) {
        $this->userDocumentDir = "../../uploads/users/$userId";
        if (!is_dir($this->userDocumentDir)) {
            mkdir($this->userDocumentDir, 0777, true);
        }
    }

    public function getDocuments() {
        return glob("$this->userDocumentDir/*.{pdf}", GLOB_BRACE);
    }

    public function searchDocumentsByDate($startDate, $endDate) {
        $documents = $this->getDocuments();
        $filteredDocuments = [];

        foreach ($documents as $document) {
            $fileDate = filemtime($document);
            if ($fileDate >= strtotime($startDate) && $fileDate <= strtotime($endDate)) {
                $filteredDocuments[] = $document;
            }
        }

        return $filteredDocuments;
    }

    public function viewPDF($documentPath) {
        if (file_exists($documentPath)) {
            header('Content-Type: application/pdf');
            readfile($documentPath);
        } else {
            throw new Exception("File not found.");
        }
    }
}

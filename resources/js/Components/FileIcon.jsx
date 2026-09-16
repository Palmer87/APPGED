import React from 'react';
import {
    FileText,
    FileSpreadsheet,
    FileImage,
    FileArchive,
    FileCode,
    FileAudio,
    FileVideo,
    File as FileDefault,
    Folder as FolderIcon
} from 'lucide-react';

export default function FileIcon({ mimeType = '', extension = '', className = 'w-6 h-6', isFolder = false }) {
    if (isFolder) {
        return <FolderIcon className={`text-amber-500 fill-amber-100 ${className}`} />;
    }

    const ext = (extension || '').toLowerCase();
    const mime = (mimeType || '').toLowerCase();

    if (ext === 'pdf' || mime.includes('pdf')) {
        return <FileText className={`text-rose-500 ${className}`} />;
    }

    if (['xlsx', 'xls', 'csv', 'ods'].includes(ext) || mime.includes('spreadsheet') || mime.includes('excel') || mime.includes('csv')) {
        return <FileSpreadsheet className={`text-emerald-600 ${className}`} />;
    }

    if (['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'].includes(ext) || mime.includes('image/')) {
        return <FileImage className={`text-sky-500 ${className}`} />;
    }

    if (['zip', 'rar', 'tar', 'gz', '7z'].includes(ext) || mime.includes('archive') || mime.includes('compressed') || mime.includes('zip')) {
        return <FileArchive className={`text-amber-600 ${className}`} />;
    }

    if (['json', 'js', 'jsx', 'ts', 'tsx', 'html', 'css', 'php', 'xml'].includes(ext) || mime.includes('javascript') || mime.includes('json')) {
        return <FileCode className={`text-indigo-500 ${className}`} />;
    }

    if (mime.includes('audio/')) {
        return <FileAudio className={`text-purple-500 ${className}`} />;
    }

    if (mime.includes('video/')) {
        return <FileVideo className={`text-rose-600 ${className}`} />;
    }

    if (['doc', 'docx', 'odt', 'rtf'].includes(ext) || mime.includes('word') || mime.includes('document')) {
        return <FileText className={`text-blue-600 ${className}`} />;
    }

    return <FileDefault className={`text-slate-400 ${className}`} />;
}

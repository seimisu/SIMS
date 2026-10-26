export const validateLibraryForm = (form, targets, video = false) => {
    const errors = {};
    if (!form.title?.trim()) errors.title = "Please enter a title.";
    else if (form.title.length > 255) errors.title = "Please shorten the title.";
    if ((form.description?.length ?? 0) > 200) errors.description = "Please shorten the description.";
    if (!targets.length) errors.targets = "Please select an audience.";
    if (!form.available_all && form.region_scopes.some(scope => scope.school_coverage?.id === "specific" && !scope.schools?.length)) errors.targets = "Please select at least one school for each region using specific school coverage.";
    if (video) {
        try {
            const url = new URL(form.video_url);
            if (!["http:", "https:"].includes(url.protocol) || form.video_url.length > 2048) throw new Error();
        } catch {
            errors.video_url = "Please enter a valid HTTP or HTTPS video link.";
        }
        const file = form.thumbnail;
        if (file && (!file.type.startsWith("image/") || !file.size)) errors.thumbnail = "Please choose a valid, non-empty preview image.";
        else if (file?.size > 5 * 1024 * 1024) errors.thumbnail = "The preview image must not exceed 5 MB.";
    } else {
        const file = form.file;
        if (!form.id && !file) errors.file = "Please select a document to upload.";
        if (file && !/\.(pdf|docx?|xlsx?|csv|jpe?g|png)$/i.test(file.name)) errors.file = "Choose a PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, JPEG, or PNG file.";
        else if (file && !file.size) errors.file = "This file is empty. Please select a file with content.";
        else if (file?.size > 20 * 1024 * 1024) errors.file = "The file must not exceed 20 MB.";
    }
    return errors;
};

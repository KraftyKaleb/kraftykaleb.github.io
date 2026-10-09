import {HttpErrorResponse} from '@angular/common/http';

/** A readable message for a failed API request, taken from the API's error body when it has one. */
export function apiErrorMessage(error: unknown): string {
    if (!(error instanceof HttpErrorResponse)) {
        return error instanceof Error ? error.message : 'Something went wrong.';
    }
    if (error.status === 0) {
        return 'Could not reach the API.';
    }

    if (error.status >= 500) {
        // Server errors carry internal details (in development, even SQL), which are no help here.
        return `The API failed with ${error.status} ${error.statusText}.`.trim();
    }

    const body: unknown = error.error;
    if (typeof body === 'object' && body !== null) {
        const {violations, detail, error: message} = body as Record<string, unknown>;
        if (Array.isArray(violations) && violations.length > 0) {
            return violations
                .map(violation => `${violation?.propertyPath ? violation.propertyPath + ': ' : ''}${violation?.message ?? ''}`)
                .join('\n');
        }
        if (typeof detail === 'string' && detail !== '') {
            return detail;
        }
        if (typeof message === 'string' && message !== '') {
            return message;
        }
    }
    return `The API answered ${error.status} ${error.statusText}.`.trim();
}

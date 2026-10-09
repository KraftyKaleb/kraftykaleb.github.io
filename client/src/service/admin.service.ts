import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {map, Observable} from 'rxjs';
import * as t from 'io-ts';
import {Category} from "@app/model/category";
import {Link} from "@app/model/link";
import {Project} from "@app/model/project";
import {Tag} from "@app/model/tag";
import {decode} from "@app/model/decode";

export interface CategoryInput {
    name: string;
    description: string;
    ordinal: number;
}

export interface TagInput {
    name: string;
    ordinal: number;
}

export interface ProjectInput {
    name: string;
    description: string;
    ordinal: number;
    categoryId: string;
    tagIds: string[];
}

export interface LinkInput {
    title: string;
    url: string;
    icon: string;
}

/** Creates, edits and deletes the site's content. Every write needs a signed-in admin. */
@Injectable({providedIn: 'root'})
export class AdminService {
    private readonly http = inject(HttpClient);
    private readonly headers = {Accept: 'application/json'};
    private readonly patchHeaders = {...this.headers, 'Content-Type': 'application/merge-patch+json'};

    public getCategories(): Observable<Category[]> {
        return this.getAll('categories', Category.codec, Category.fromJson);
    }

    public saveCategory(id: string | null, input: CategoryInput): Observable<Category> {
        return this.save('categories', id, input, Category.codec, Category.fromJson);
    }

    public deleteCategory(id: string): Observable<void> {
        return this.delete('categories', id);
    }

    public getTags(): Observable<Tag[]> {
        return this.getAll('tags', Tag.codec, Tag.fromJson);
    }

    public saveTag(id: string | null, input: TagInput): Observable<Tag> {
        return this.save('tags', id, input, Tag.codec, Tag.fromJson);
    }

    public deleteTag(id: string): Observable<void> {
        return this.delete('tags', id);
    }

    public getProjects(): Observable<Project[]> {
        return this.getAll('projects', Project.codec, Project.fromJson);
    }

    public getProject(id: string): Observable<Project> {
        return this.http.get<unknown>(`api/projects/${id}`, {headers: this.headers}).pipe(
            map(json => Project.fromJson(decode(Project.codec, json)))
        );
    }

    public saveProject(id: string | null, input: ProjectInput): Observable<Project> {
        const body = {
            name: input.name,
            description: input.description,
            ordinal: input.ordinal,
            category: this.iri('categories', input.categoryId),
            tags: input.tagIds.map(tagId => this.iri('tags', tagId))
        };
        return this.save('projects', id, body, Project.codec, Project.fromJson);
    }

    public deleteProject(id: string): Observable<void> {
        return this.delete('projects', id);
    }

    public saveLink(id: string | null, projectId: string, input: LinkInput): Observable<Link> {
        const body = {...input, project: this.iri('projects', projectId)};
        return this.save('links', id, body, Link.codec, Link.fromJson);
    }

    public deleteLink(id: string): Observable<void> {
        return this.delete('links', id);
    }

    private getAll<A, O, M>(resource: string, codec: t.Type<A, O>, fromJson: (json: A) => M): Observable<M[]> {
        return this.http.get<unknown>(`api/${resource}`, {headers: this.headers}).pipe(
            map(json => decode(t.array(codec), json).map(fromJson))
        );
    }

    /** Creates the resource when id is null, otherwise updates the given fields of an existing one. */
    private save<A, O, M>(resource: string, id: string | null, body: object, codec: t.Type<A, O>, fromJson: (json: A) => M): Observable<M> {
        const request = id === null
            ? this.http.post<unknown>(`api/${resource}`, body, {headers: this.headers})
            : this.http.patch<unknown>(`api/${resource}/${id}`, body, {headers: this.patchHeaders});
        return request.pipe(map(json => fromJson(decode(codec, json))));
    }

    private delete(resource: string, id: string): Observable<void> {
        return this.http.delete<void>(`api/${resource}/${id}`);
    }

    /** How the API refers to another resource in a request body, e.g. "/api/tags/{id}". */
    private iri(resource: string, id: string): string {
        return `/api/${resource}/${id}`;
    }
}

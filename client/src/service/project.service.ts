import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {map, Observable} from 'rxjs';
import {Category} from "@app/model/category";
import {Project} from "@app/model/project";
import {Link} from "@app/model/link";

/** A category as GET /api/categories returns it. */
interface CategoryResponse {
    id: string;
    name: string;
    description: string;
}

/** A project as GET /api/projects returns it. */
interface ProjectResponse {
    name: string;
    description: string;
    category: { id: string };
    tags: { name: string }[];
    links: { title: string, url: string, icon: string }[];
}

@Injectable({providedIn: 'root'})
export class ProjectService {
    private readonly http = inject(HttpClient);
    private readonly headers = {Accept: 'application/json'};

    /** All project categories, in display order. */
    public getCategories(): Observable<Category[]> {
        return this.http.get<CategoryResponse[]>('api/categories', {headers: this.headers}).pipe(
            map(categories => categories.map(category => new Category(category.id, category.name, category.description)))
        );
    }

    /** All projects, in display order. */
    public getProjects(): Observable<Project[]> {
        return this.http.get<ProjectResponse[]>('api/projects', {headers: this.headers}).pipe(
            map(projects => projects.map(project => new Project(
                project.name,
                project.description,
                project.category.id,
                project.tags.map(tag => tag.name),
                project.links.map(link => new Link(link.title, link.url, link.icon))
            )))
        );
    }
}

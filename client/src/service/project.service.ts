import {inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {map, Observable} from 'rxjs';
import {Project, ProjectCategory} from "@app/model/project";
import {Link} from "@app/model/link";

/** A project as GET /api/projects returns it. */
interface ProjectResponse {
    name: string;
    description: string;
    category: ProjectCategory;
    tags: { name: string }[];
    links: { title: string, url: string, icon: string }[];
}

@Injectable({providedIn: 'root'})
export class ProjectService {
    private readonly http = inject(HttpClient);

    /** All projects, in display order. */
    public getProjects(): Observable<Project[]> {
        return this.http.get<ProjectResponse[]>('api/projects', {headers: {Accept: 'application/json'}}).pipe(
            map(projects => projects.map(project => new Project(
                project.name,
                project.description,
                project.category,
                project.tags.map(tag => tag.name),
                project.links.map(link => new Link(link.title, link.url, link.icon))
            )))
        );
    }
}

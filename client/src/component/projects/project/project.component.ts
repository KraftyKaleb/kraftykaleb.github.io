import {Component, input, signal} from '@angular/core';
import {Project} from "@app/model/project";

@Component({
  selector: 'app-project',
  standalone: true,
  imports: [],
  templateUrl: './project.component.html',
  styleUrl: './project.component.css'
})
export class ProjectComponent {
  public readonly project = input.required<Project>();
}

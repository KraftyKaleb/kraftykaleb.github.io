import {Component, inject} from '@angular/core';
import {toSignal} from "@angular/core/rxjs-interop";
import {catchError, of} from "rxjs";
import {SkillService} from "@app/service/skill.service";

@Component({
  selector: 'app-skills',
  standalone: true,
  templateUrl: './skills.component.html',
  styleUrl: './skills.component.css'
})
export class SkillsComponent {
  private readonly skillService = inject(SkillService);

  protected readonly skills = toSignal(this.skillService.getSkills().pipe(catchError(error => {
    console.error('Could not load skills', error);
    return of([]);
  })), {initialValue: []});
}

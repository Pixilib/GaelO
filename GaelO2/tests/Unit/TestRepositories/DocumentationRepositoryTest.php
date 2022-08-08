<?php

namespace Tests\Unit\TestRepositories;

use App\GaelO\Constants\Constants;
use App\GaelO\Repositories\DocumentationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Models\Documentation;
use App\Models\Study;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DocumentationRepositoryTest extends TestCase
{
    private DocumentationRepository $documentationRepository;

    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    use RefreshDatabase;

    public function runDatabaseMigrations()
    {
        $this->baseRunDatabaseMigrations();
        $this->artisan('db:seed');
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->documentationRepository = new DocumentationRepository(new Documentation());
        $this->study = Study::factory()->create();
        $this->study2 = Study::factory()->create();
    }

    public function testCreateDocumentation(){

        $documenationEntity = $this->documentationRepository->createDocumentation('documentation', $this->study->name , '1.0', true,
                true, true, true);

        $documentation = Documentation::find($documenationEntity['id'])->toArray();

        $this->assertEquals($documenationEntity['name'], $documentation['name']);
        $this->assertEquals($documenationEntity['version'], $documentation['version']);
    }

    public function testGetDocumentation(){
        $createdDocumentation = Documentation::factory()->studyName($this->study->name)->create();
        $documentation = $this->documentationRepository->find($createdDocumentation->id, false);

        $this->assertEquals($createdDocumentation->name, $documentation['name']);
        $this->assertEquals($createdDocumentation->version, $documentation['version']);
    }

    public function testDeleteDocumentation(){
        $createdDocumentation = Documentation::factory()->studyName($this->study->name)->create();
        $this->documentationRepository->delete($createdDocumentation['id']);
        $this->expectException(ModelNotFoundException::class);
        Documentation::findOrFail($createdDocumentation['id']);

    }

    public function testDocumentationOfStudy(){

        Documentation::factory()->studyName($this->study->name)->count(5)->create();
        Documentation::factory()->studyName($this->study2->name)->count(10)->create();

        $documenationStudy1 = $this->documentationRepository->getDocumentationsOfStudy($this->study->name);
        $this->assertEquals(5, sizeof($documenationStudy1));
        $documenationStudy1 = $this->documentationRepository->getDocumentationsOfStudy($this->study2->name);
        $this->assertEquals(10, sizeof($documenationStudy1));
    }

    public function testDocumentationOfStudyWithRole(){

        Documentation::factory()->studyName($this->study->name)->investigator()->count(5)->create();
        Documentation::factory()->studyName($this->study->name)->reviewer()->count(15)->create();
        Documentation::factory()->studyName($this->study2->name)->investigator()->count(10)->create();

        $documenationStudy1Investigator = $this->documentationRepository->getDocumentationOfStudyWithRole($this->study->name, Constants::ROLE_INVESTIGATOR);
        $this->assertEquals(5, sizeof($documenationStudy1Investigator));
        $documenationStudy1Supervisor = $this->documentationRepository->getDocumentationOfStudyWithRole($this->study->name, Constants::ROLE_REVIEWER);
        $this->assertEquals(15, sizeof($documenationStudy1Supervisor));
        $documenationStudy2Investigator = $this->documentationRepository->getDocumentationOfStudyWithRole($this->study2->name, Constants::ROLE_INVESTIGATOR);
        $this->assertEquals(10, sizeof($documenationStudy2Investigator));
    }

    public function testUpdateDocumentation() {

        $documentation = Documentation::factory()->create();
        //Sleep 1 sec to change time (change less than 1 sec seems not seen by casting)
        sleep(1);

        $this->documentationRepository->updateDocumentation(
            $documentation->id,
            $documentation->name,
            $documentation->study_name,
            '1.2',
            $documentation->investigator,
            $documentation->controller,
            $documentation->monitor,
            $documentation->reviewer
        );
        $updatedDocumentation = Documentation::find($documentation->id);

        $this->assertEquals('1.2', $updatedDocumentation->version);
        $this->assertNotEquals($documentation->document_date, $updatedDocumentation->document_date);
    }

    public function testUpdateDocumentationRoles() {

        $documentation = Documentation::factory()->create();

        $this->documentationRepository->updateDocumentation(
            $documentation->id,
            $documentation->name,
            $documentation->study_name,
            $documentation->version,
            true,
            true,
            $documentation->monitor,
            $documentation->reviewer
        );

        $updatedDocumentation = Documentation::find($documentation->id);

        $this->assertTrue(filter_var($updatedDocumentation->controller, FILTER_VALIDATE_BOOLEAN));
        $this->assertTrue(filter_var($updatedDocumentation->investigator, FILTER_VALIDATE_BOOLEAN));
        $this->assertFalse(filter_var($updatedDocumentation->monitor, FILTER_VALIDATE_BOOLEAN));
        $this->assertFalse(filter_var($updatedDocumentation->reviewer, FILTER_VALIDATE_BOOLEAN));

        $this->documentationRepository->updateDocumentation(
            $updatedDocumentation->id,
            $updatedDocumentation->name,
            $updatedDocumentation->study_name,
            $updatedDocumentation->version,
            $updatedDocumentation->investigator,
            $updatedDocumentation->controller,
            true,
            $updatedDocumentation->reviewer
        );

        $updatedDocumentation = Documentation::find($documentation->id);

        $this->assertTrue(filter_var($updatedDocumentation->controller, FILTER_VALIDATE_BOOLEAN));
        $this->assertTrue(filter_var($updatedDocumentation->investigator, FILTER_VALIDATE_BOOLEAN));
        $this->assertTrue(filter_var($updatedDocumentation->monitor, FILTER_VALIDATE_BOOLEAN));
        $this->assertFalse(filter_var($updatedDocumentation->reviewer, FILTER_VALIDATE_BOOLEAN));

    }

    public function testUpdateDocumentationRolesToFalse() {

        $documentation = Documentation::factory()->investigator()->create();
        $this->assertTrue(filter_var($documentation->investigator, FILTER_VALIDATE_BOOLEAN));

        $this->documentationRepository->updateDocumentation(
            $documentation->id,
            $documentation->name,
            $documentation->study_name,
            $documentation->version,
            false,
            $documentation->controller,
            $documentation->monitor,
            $documentation->reviewer
        );

        $updatedDocumentation = Documentation::find($documentation->id);

        $this->assertFalse(filter_var($updatedDocumentation->investigator, FILTER_VALIDATE_BOOLEAN));
    }

}

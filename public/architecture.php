<?php require_once __DIR__.'/../src/layout.php'; page_start('Cloud Architecture (AWS)'); ?>

<div class="row justify-content-center mb-5">
    <div class="col-lg-10">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold text-dark mb-3"><i class="fab fa-aws text-warning me-2"></i> Cloud Architecture</h1>
            <p class="lead text-muted">HostelCloud is natively designed and deployed using the Amazon Web Services (AWS) ecosystem, optimizing the AWS Free Tier for scalable, secure, and highly available cloud hosting.</p>
        </div>

        <div class="card shadow-sm border-0 mb-5 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h3 class="fw-bold text-primary mb-4">Highly Available 3-Tier Architecture Diagram</h3>
                
                <div class="bg-light p-4 rounded-3 mb-4 text-center overflow-auto" style="min-height: 250px;">
                    <div class="mermaid">
                        graph TD
                            subgraph Internet
                                Users((Users / Internet))
                            end
                            
                            subgraph "AWS Cloud (Free Tier)"
                                ALB[Application Load Balancer]
                                
                                subgraph "Auto Scaling Group / EC2"
                                    EC2_1(EC2 Instance 1<br>Web/App Server)
                                    EC2_2(EC2 Instance 2<br>Web/App Server)
                                end
                                
                                RDS[(Amazon RDS<br>MySQL Database)]
                                S3[("Amazon S3<br>Object Storage (Images/Docs)")]
                                CW((CloudWatch<br>Monitoring))
                                IAM{{IAM & Security Groups}}
                                
                                ALB --> EC2_1
                                ALB --> EC2_2
                                
                                EC2_1 --> RDS
                                EC2_2 --> RDS
                                
                                EC2_1 -.-> S3
                                EC2_2 -.-> S3
                                
                                CW -.-> EC2_1
                                CW -.-> EC2_2
                                CW -.-> RDS
                            end
                            
                            Users --> ALB
                            
                            classDef aws fill:#FF9900,stroke:#232F3E,stroke-width:2px,color:#fff;
                            classDef compute fill:#F58536,stroke:#232F3E,stroke-width:2px,color:#fff;
                            classDef storage fill:#3F8624,stroke:#232F3E,stroke-width:2px,color:#fff;
                            classDef db fill:#3355CC,stroke:#232F3E,stroke-width:2px,color:#fff;
                            classDef net fill:#8C4FFF,stroke:#232F3E,stroke-width:2px,color:#fff;
                            classDef mgmt fill:#D13212,stroke:#232F3E,stroke-width:2px,color:#fff;
                            
                            class ALB net;
                            class EC2_1,EC2_2 compute;
                            class S3 storage;
                            class RDS db;
                            class CW mgmt;
                            class IAM aws;
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 rounded-4">
            <div class="card-body p-4 p-md-5">
                <h3 class="fw-bold text-primary mb-4">Infrastructure Components</h3>
                
                <div class="row g-4">
                    <!-- IAM -->
                    <div class="col-md-12">
                        <div class="d-flex align-items-start">
                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3 me-4 text-center" style="width: 60px; height: 60px; flex-shrink: 0;">
                                <i class="fas fa-users-cog fa-lg mt-1"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">IAM (Identity and Access Management)</h5>
                                <p class="text-muted mb-0"><strong>Requirement: Required.</strong> Handles user, role, and access management. The application accesses S3 via securely scoped IAM credentials, ensuring tight permission handling.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- EC2 -->
                    <div class="col-md-12">
                        <div class="d-flex align-items-start">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-4 text-center" style="width: 60px; height: 60px; flex-shrink: 0;">
                                <i class="fas fa-server fa-lg mt-1"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Amazon EC2 (Elastic Compute Cloud)</h5>
                                <p class="text-muted mb-0"><strong>Requirement: Required.</strong> Hosts the application/backend server on Ubuntu Linux instances, processing all PHP and web logic.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Groups -->
                    <div class="col-md-12">
                        <div class="d-flex align-items-start">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3 me-4 text-center" style="width: 60px; height: 60px; flex-shrink: 0;">
                                <i class="fas fa-shield-alt fa-lg mt-1"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Security Groups</h5>
                                <p class="text-muted mb-0"><strong>Requirement: Required.</strong> Acts as a virtual firewall for EC2 and RDS instances, strictly controlling inbound and outbound traffic (e.g., allowing only HTTP/HTTPS from the internet).</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- RDS -->
                    <div class="col-md-12">
                        <div class="d-flex align-items-start">
                            <div class="bg-info bg-opacity-10 text-info rounded-circle p-3 me-4 text-center" style="width: 60px; height: 60px; flex-shrink: 0;">
                                <i class="fas fa-database fa-lg mt-1"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Amazon RDS (MySQL)</h5>
                                <p class="text-muted mb-0"><strong>Requirement: Required.</strong> A Free-Tier managed SQL database handling all persistent relational data (users, bookings, rooms).</p>
                            </div>
                        </div>
                    </div>

                    <!-- S3 -->
                    <div class="col-md-12">
                        <div class="d-flex align-items-start">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 me-4 text-center" style="width: 60px; height: 60px; flex-shrink: 0;">
                                <i class="fas fa-cloud-upload-alt fa-lg mt-1"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Amazon S3 (Simple Storage Service)</h5>
                                <p class="text-muted mb-0"><strong>Requirement: Required.</strong> Stores all uploaded images, PDFs, and documents natively via the AWS SDK for PHP, bypassing local storage constraints.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CloudWatch -->
                    <div class="col-md-12">
                        <div class="d-flex align-items-start">
                            <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle p-3 me-4 text-center" style="width: 60px; height: 60px; flex-shrink: 0;">
                                <i class="fas fa-chart-line fa-lg mt-1"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Amazon CloudWatch</h5>
                                <p class="text-muted mb-0"><strong>Requirement: Required.</strong> Provides centralized monitoring, performance metrics, and log collection across the EC2 and RDS infrastructure.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Application Load Balancer -->
                    <div class="col-md-12">
                        <div class="d-flex align-items-start">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-4 text-center" style="width: 60px; height: 60px; flex-shrink: 0;">
                                <i class="fas fa-network-wired fa-lg mt-1"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Application Load Balancer (ALB)</h5>
                                <p class="text-muted mb-0"><strong>Requirement: Optional - Bonus Marks.</strong> Included in our architecture to distribute incoming internet traffic across multiple EC2 instances, ensuring high availability and fault tolerance.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5 mb-4">
            <a href="<?=url()?>" class="btn btn-outline-secondary px-4"><i class="fas fa-arrow-left me-2"></i> Return to Home</a>
        </div>
    </div>
</div>

<!-- Load Mermaid JS for Architecture Diagram -->
<script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
<script>
    mermaid.initialize({ startOnLoad: true, theme: 'default' });
</script>

<?php page_end(); ?>

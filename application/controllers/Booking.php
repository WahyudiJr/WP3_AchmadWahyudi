<?php 

defined('BASEPATH') or exit('No Direct Script Access Allowed');
date_default_timezone_set('Asia/Jakarta');

class Booking extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        cek_login();
        $this->load->model(['ModelBooking','ModelUser']);
    }

    public function index()
    {
        $id = ['bo.id_user' => $this->uri->segment(3)];
        $id_user = $this->session->userdata('id_user');
        $data['booking'] = $this->ModelBooking->joinOrder($id)->result();

        $user = $this->ModelUser->cekData(['email' => $this->session->userdata('email')])->row_array();
        foreach ($user as $a) {
            $data = [
                'image' => $user['image'],
                'user' => $user['nama'],
                'email' => $user['email'],
                'tanggal_input' => $user['tanggal_input']
            ];
        }
        $dtb = $this->ModelBooking->showtemp(['id_user' => $id_user])->num_rows();

        if ($dtb < 1) {
            $this->session->set_flashdata('pesan', '<div class="alert alert-message alert-danger" role="alert"> Tidak Ada Buku dikeranjang</div>');
            redirect(base_url());
        }
        else {
            $data['temp'] = $this->db->query("SELECT image, judul_buku, penulis, penerbit, tahun_terbit, id_buku, FROM temp WHERE id_user='$id_user'")->result_array();
        }
        $data['judul'] = "Data Booking";

        $this->load->view('templates/templates-user/header', $data);
        $this->load->view('booking/data-booking', $data);
        $this->load->view('templates/templates-user/modal', $data);
        $this->load->view('templates/templates-user/footer', $data);
    }

    public function tambahBooking()
    {
        $id_buku = $this->uri->segment(3);

        // memilih data buku yang untuk dimasukkan ke tabel temp/keranjang melalui variabel $isi
        $d = $this->db->query("SELECT*FROM buku WHERE id='$id_buku'")->row();

        // berupa data-data yg akan disimpan ke dalam tabel temp/keranjang
        $isi = [
            'id_buku' => $id_buku,
            'judul_buku' => $d->judul_buku,
            'id_user' => $this->session->userdata('id_user'),
            'email_user' => $this->session->userdata('email'),
            'tgl_booking' => date('Y-M-D H:I:S'),
            'image' => $d->image,
            'penulis' => $d->penulis,
            'penerbit' => $d->penerbit,
            'tahun_terbit' => $d->tahun_terbit
        ];

        // cek apakah buku yg diklik booking sudah ada dikeranjang
        $temp = $this->ModelBooking->getDataWhere('temp', ['id_buku' => $id_buku])->num_rows();

        $userid = $this->session->userdata('id_user');

        // cek jika sudah memasukkan 3 buku untuk dibooking dalam keranjang
        $tempuser = $this->db->query("SELECT*FROM temp WHERE id_user='$userid'")->num_rows();

        // cek jika masih ada booking buku yg belum diambil
        $databooking = $this->db->query("SELECT*FROM booking WHERE id_user='$userid'")->num_rows();
        if($databooking > 0) {
            $this->session->set_flashdata('pesan', '<div class="alert alert-message" role="alert"> Masih Ada booking buku sebelumnya yg belum diambil <br> Ambil buku yg dibooking atau tunggu 1X24 Jam untuk bisa booking kembali </div>');
            redirect(base_url());
        }

        // jika buku yg diklik booking sudah ada dikeranjang
        if($temp > 0) {
            $this->session->set_flashdata('pesan', '<div class="alert alert-danger alert-message" role="alert"> Buku ini sudah anda booking </div>');
            redirect(base_url() . 'home');
        }

        // jika buku yg akan dibooking sudah mencapai 3 item
        if ($tempuser == 3) {
            $this->session->set_flashdata('pesan', '<div class="alert alert-danger alert-message" role="alert"> Booking Buku Tidak Boleh Lebih dari 3 </div>');
            redirect(base_url() . 'home');
        }

        // membuat tabel temp jika belum ada
        $this->ModelBooking->createTemp();
        $this->ModelBooking->insertData('temp', $isi);

        // pesan ketika berhasil memasukkan buku ke keranjang
        $this->session->set_flashdata('pesan', '<div class="alert alert-success alert-message" role="alert"> Buku berhasil ditambahkan ke keranjang </div>');
        redirect(base_url() . 'home');
    }

    public function hapusBooking()
    {
        $id_buku = $this->uri->segment(3);
        $id_user = $this->session->userdata('id_user');

        $this->ModelBooking->deleteData(['id_buku' => $id_buku], 'temp');
        $kosong = $this->db->query("SELECT*FROM temp WHERE id_user='$id_user'")->num_rows();

        if ($kosong < 1) {
            $this->session->set_flashdata('pesan', '<div class="alert alert-message alert-danger" role="alert"> Tidak Ada Buku dikeranjang </div>');
            redirect(base_url());
        }
        else {
            redirect(base_url() . 'booking');
        }
    }
}